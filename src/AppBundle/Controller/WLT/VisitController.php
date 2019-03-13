<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Controller\WLT;

use AppBundle\Entity\WLT\Visit;
use AppBundle\Form\Type\WLT\VisitType;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WLT\Repository;
use AppBundle\Repository\WLT\VisitRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/visita")
 */
class VisitController extends Controller
{
    /**
     * @Route("/nueva", name="work_linked_training_visit_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::WLT_TEACHER, $organization);

        $academicYear = $organization->getCurrentAcademicYear();
        $person = $this->getUser()->getPerson();
        $teacher = $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        $visit = new Visit();
        $visit
            ->setDateTime(new \DateTime());

        if ($teacher) {
            $visit->setTeacher($teacher);
        }

        $this->getDoctrine()->getManager()->persist($visit);

        return $this->indexAction(
            $request,
            $translator,
            $userExtensionService,
            $security,
            $teacherRepository,
            $groupRepository,
            $visit
        );
    }

    /**
     * @Route("/{id}", name="work_linked_training_visit_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        Visit $visit
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(OrganizationVoter::WLT_TEACHER, $organization);

        $em = $this->getDoctrine()->getManager();

        $readOnly = false;

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(OrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(OrganizationVoter::DEPARTMENT_HEAD, $organization);

        $groups = [];
        $teachers = [];

        $teacher = null;

        if (false === $isManager) {
            $person = $this->getUser()->getPerson();
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor -> ver sólo sus grupos
            $teacher =
                $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($teacher) {
                $groups = $groupRepository->findByAcademicYearAndTeacher($academicYear, $teacher);
            }
        }
        if (!$isManager && !$isDepartmentHead && $teacher) {
            $teachers = [$teacher];
        } elseif ($groups) {
            $teachers = $teacherRepository->findByGroups($groups);
        }

        $form = $this->createForm(VisitType::class, $visit, [
            'disabled' => !$isManager && !$isWltManager && $teacher !== $visit->getTeacher(),
            'teachers' => $teachers
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_visit'));
                return $this->redirectToRoute('work_linked_training_visit_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_visit'));
            }
        }

        $title = $translator->trans(
            $visit->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_visit'
        );

        $breadcrumb = [
                ['fixed' => $title]
        ];

        return $this->render('wlt/visit/form.html.twig', [
            'menu_path' => 'work_linked_training_visit_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{page}", name="work_linked_training_visit_list",
     *     requirements={"page" = "\d+"}, defaults={"page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        Security $security,
        TranslatorInterface $translator,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(OrganizationVoter::WLT_TEACHER, $organization);
        $readOnly = false;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('v')
            ->addSelect('t')
            ->addSelect('p')
            ->addSelect('w')
            ->addSelect('c')
            ->from(Visit::class, 'v')
            ->join('v.teacher', 't')
            ->join('t.person', 'p')
            ->join('v.workcenter', 'w')
            ->join('w.company', 'c')
            ->addOrderBy('v.dateTime', 'DESC');

        $isManager = $security->isGranted(OrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(OrganizationVoter::DEPARTMENT_HEAD, $organization);

        $groups = [];
        $teacher = null;

        if (false === $isManager) {
            $person = $this->getUser()->getPerson();
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor -> ver sólo sus grupos
            $teacher =
                $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($teacher) {
                $groups = $groupRepository->findByAcademicYearAndTeacher($academicYear, $teacher);
            }
        }

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('v.detail LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        if ($groups) {
            $teachers = $teacherRepository->findByGroups($groups);
            $queryBuilder
                ->andWhere('t IN (:teachers)')
                ->setParameter('teachers', $teachers);
        }

        if (!$isManager && !$isDepartmentHead && $teacher) {
            // ver sólo las suyas
            $queryBuilder
                ->andWhere('v.teacher = :teacher')
                ->setParameter('teacher', $teacher);
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.list', [], 'wlt_visit');

        return $this->render('wlt/visit/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_visit',
            'read_only' => $readOnly,
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/eliminar", name="work_linked_training_visit_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        VisitRepository $visitRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_WORK_LINKED_TRAINING, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('work_linked_training_visit_list');
        }

        $visits = $visitRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $visitRepository->deleteFromList($visits);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_visit'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_visit'));
            }
            return $this->redirectToRoute('work_linked_training_visit_list');
        }

        $title = $this->get('translator')->trans('title.delete', [], 'wlt_visit');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/visit/delete.html.twig', [
            'menu_path' => 'work_linked_training_visit_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $visits
        ]);
    }
}
