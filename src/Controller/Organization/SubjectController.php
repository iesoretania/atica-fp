<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace App\Controller\Organization;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Subject;
use App\Entity\Edu\Teaching;
use App\Form\Type\Edu\SubjectType;
use App\Form\Type\Edu\TeachingType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\SubjectRepository;
use App\Security\Edu\AcademicYearVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/centro/materia")
 */
class SubjectController extends AbstractController
{
    /**
     * @Route("/nuevo", name="organization_subject_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_subject_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Subject $subject = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $subject) {
            $subject = new Subject();
            $academicYear = $organization->getCurrentAcademicYear();
            $em->persist($subject);
        } else {
            $academicYear = $subject->getGrade()->getTraining()->getAcademicYear();
            $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);
        }

        $form = $this->createForm(SubjectType::class, $subject, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_subject'));
                return $this->redirectToRoute('organization_subject_list', [
                    'academicYear' => $academicYear->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_subject'));
            }
        }

        $title = $translator->trans(
            $subject->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_subject'
        );

        $breadcrumb = [
            $subject->getId() !== null ?
                ['fixed' => $subject->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_subject')]
        ];

        return $this->render('organization/subject/form.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_subject_list", requirements={"page" = "\d+"},
     *     defaults={"academicYear" = null, "page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('s')
            ->from(Subject::class, 's')
            ->join('s.grade', 'g')
            ->join('g.training', 't')
            ->orderBy('s.name');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('s.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_subject');

        return $this->render('organization/subject/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_subject',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_subject_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        SubjectRepository $subjectRepository,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        if ($academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createNotFoundException();
        }

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_subject_list', ['academicYear' => $academicYear->getId()]);
        }

        $subjects = $subjectRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $subjectRepository->deleteFromList($subjects);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_subject'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_subject'));
            }
            return $this->redirectToRoute('organization_subject_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/subject/delete.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_subject')]],
            'title' => $translator->trans('title.delete', [], 'edu_subject'),
            'subjects' => $subjects
        ]);
    }

    /**
     * @Route("/asignacion/nueva/{id}", name="organization_teaching_new", methods={"GET", "POST"})
     */
    public function formNewTeachingAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Subject $subject
    ) {
        $teaching = new Teaching();
        $teaching
            ->setSubject($subject);

        $this->getDoctrine()->getManager()->persist($teaching);

        return $this->formTeachingAction($request, $userExtensionService, $translator, $teaching);
    }

    /**
     * @Route("/asignacion/{id}", name="organization_teaching_edit", requirements={"id" = "\d+"},
     *     methods={"GET", "POST"})
     */
    public function formTeachingAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Teaching $teaching
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        $academicYear = $teaching->getSubject()->getGrade()->getTraining()->getAcademicYear();

        $form = $this->createForm(TeachingType::class, $teaching, ['subject' => $teaching->getSubject()]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_teaching'));
                return $this->redirectToRoute('organization_subject_list', [
                    'academicYear' => $academicYear->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_teaching'));
            }
        }

        $title = $translator->trans(
            $teaching->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_teaching'
        );

        $breadcrumb = [
            [
                'fixed' => $academicYear->getDescription(),
                'routeName' => 'organization_subject_list',
                'routeParams' => ['academicYear' => $academicYear->getId()]
            ],
            $teaching->getId() !== null ?
                ['fixed' => $teaching->getTeacher()->getPerson() .
                    ' - ' . $teaching->getSubject()->getName() .
                    ' (' . $teaching->getGroup()->getName() . ')'] :
                ['fixed' => $translator->trans('title.new', [], 'edu_teaching')]
        ];

        return $this->render('organization/subject/teaching_form.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'teaching' => $teaching,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/asignacion/eliminar/{id}", name="organization_teaching_delete", requirements={"id" = "\d+"},
     *     methods={"GET", "POST"})
     */
    public function deleteTeachingAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Teaching $teaching
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $academicYear = $teaching->getGroup()->getGrade()->getTraining()->getAcademicYear();

        $em = $this->getDoctrine()->getManager();

        if ($request->get('confirm', '') === 'ok') {
            try {
                $em->remove($teaching);
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_teaching'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_teaching'));
            }
            return $this->redirectToRoute('organization_subject_list', [
                'academicYear' => $academicYear->getId()
            ]);
        }

        $title = $translator->trans('title.delete', [], 'edu_teaching');

        $breadcrumb = [
            [
                'fixed' => $academicYear->getDescription(),
                'routeName' => 'organization_subject_list',
                'routeParams' => ['academicYear' => $academicYear->getId()]
            ],
            [
                'fixed' => $teaching->getTeacher()->getPerson() .
                    ' - ' . $teaching->getSubject()->getName() .
                    ' (' . $teaching->getGroup()->getName() . ')',
                'routeName' => 'organization_teaching_edit',
                'routeParams' => ['id' => $teaching->getId()]
            ],
            [
                'fixed' => $translator->trans('title.delete', [], 'edu_teaching')
            ]
        ];

        return $this->render('organization/subject/teaching_delete.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'teaching' => $teaching,
            'title' => $title
        ]);
    }
}
