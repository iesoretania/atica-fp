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
use App\Entity\Edu\Grade;
use App\Form\Type\Edu\GradeType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\GradeRepository;
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
 * @Route("/centro/nivel")
 */
class GradeController extends AbstractController
{
    /**
     * @Route("/nuevo", name="organization_grade_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_grade_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Grade $grade = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $grade) {
            $grade = new Grade();
            $em->persist($grade);
        }

        $form = $this->createForm(GradeType::class, $grade, [
            'is_admin' => $this->isGranted('ROLE_ADMIN')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_grade'));
                return $this->redirectToRoute('organization_grade_list', [
                    'academicYear' => $grade->getTraining()->getAcademicYear()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_grade'));
            }
        }

        $title = $translator->trans(
            $grade->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_grade'
        );

        $breadcrumb = [
            $grade->getId() !== null ?
                ['fixed' => $grade->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_grade')]
        ];

        return $this->render('organization/grade/form.html.twig', [
            'menu_path' => 'organization_grade_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="organization_grade_list", requirements={"page" = "\d+"},
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
            ->select('g')
            ->addSelect('gr')
            ->from(Grade::class, 'g')
            ->orderBy('g.name')
            ->innerJoin('g.training', 't')
            ->leftJoin('g.groups', 'gr');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('g.name LIKE :tq')
                ->orWhere('t.name LIKE :tq')
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

        $title = $translator->trans('title.list', [], 'edu_grade');

        return $this->render('organization/grade/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_grade',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="organization_grade_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        GradeRepository $gradeRepository,
        TranslatorInterface $translator,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(AcademicYearVoter::MANAGE, $academicYear);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_grade_list', ['academicYear' => $academicYear->getId()]);
        }

        $grades = $gradeRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $gradeRepository->deleteFromList($grades);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_grade'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_grade'));
            }
            return $this->redirectToRoute('organization_grade_list', ['academicYear' => $academicYear->getId()]);
        }

        return $this->render('organization/grade/delete.html.twig', [
            'menu_path' => 'organization_grade_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'edu_grade')]],
            'title' => $translator->trans('title.delete', [], 'edu_grade'),
            'grades' => $grades
        ]);
    }
}
