<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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

namespace App\Controller\WLT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Entity\WLT\Visit;
use App\Form\Type\WLT\VisitType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WLT\ProjectRepository;
use App\Repository\WLT\VisitRepository;
use App\Repository\WLT\WLTGroupRepository;
use App\Repository\WLT\WLTTeacherRepository;
use App\Security\Edu\EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WLT\VisitVoter;
use App\Security\WLT\WLTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/dual/visita")
 */
class VisitController extends AbstractController
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
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_CREATE_VISIT, $organization);

        $academicYear = $organization->getCurrentAcademicYear();
        /** @var Person $person */
        $person = $this->getUser();
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
            $wltGroupRepository,
            $wltTeacherRepository,
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
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository,
        Visit $visit
    ) {
        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $visit->getTeacher() ? $visit->getTeacher()->getAcademicYear() : $organization->getCurrentAcademicYear();

        $em = $this->getDoctrine()->getManager();

        $readOnly = !$this->isGranted(VisitVoter::MANAGE, $visit);

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $teacher = null;

        if (!$isManager) {
            /** @var Person $person */
            $person = $this->getUser();

            if (!$isWltManager) {
                // no es administrador ni coordinador de FP:
                // puede ser jefe de departamento, tutor de grupo o profesor -> ver sólo sus grupos
                $teacher =
                    $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

                if ($teacher) {
                    $groups = $wltGroupRepository->findByAcademicYearAndWLTTeacherPerson($academicYear, $person);
                }
            } else {
                $groups = $wltGroupRepository->findByAcademicYearAndWLTTeacherPerson($academicYear, $person);
            }
        } else {
            $groups = $wltGroupRepository->findByOrganizationAndAcademicYear($organization, $academicYear);
        }
        $teachers = [];
        if (!$isManager && !$isDepartmentHead && $teacher && !$readOnly) {
            $teachers = [$teacher];
        } elseif ($groups) {
            $teachers = $wltTeacherRepository->findByGroupsOrEducationalTutor($groups, $academicYear);
        }

        $form = $this->createForm(VisitType::class, $visit, [
            'disabled' => $readOnly,
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
            $visit->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wlt_visit'
        );

        $breadcrumb = [
                ['fixed' => $title]
        ];

        return $this->render('wlt/visit/form.html.twig', [
            'menu_path' => 'work_linked_training_visit_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_visit_list",
     *     requirements={"page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $groupRepository,
        ProjectRepository $projectRepository,
        Security $security,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization);
        $allowNew = $this->isGranted(WLTOrganizationVoter::WLT_CREATE_VISIT, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('v')
            ->distinct(true)
            ->addSelect('t')
            ->addSelect('p')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('pr')
            ->addSelect('se')
            ->addSelect('sep')
            ->addSelect('seg')
            ->from(Visit::class, 'v')
            ->join('v.teacher', 't')
            ->join('t.person', 'p')
            ->join('v.workcenter', 'w')
            ->join('w.company', 'c')
            ->leftJoin('v.projects', 'pr')
            ->leftJoin('v.studentEnrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->leftJoin('se.group', 'seg')
            ->addOrderBy('v.dateTime', 'DESC');

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(WLTOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $projects = [];

        /** @var Person $person */
        $person = $this->getUser();
        if (!$isWltManager && !$isManager && !$isDepartmentHead) {
            // no es administrador ni coordinador de FP ni jefe de familia profesional:
            // puede ser tutor de grupo  -> ver sólo visitas de los
            // estudiantes de sus grupos
            $groups = $groupRepository->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $projectRepository->findByManager($person);
        } elseif ($isDepartmentHead) {
            $projects = $projectRepository->findByDepartmentHeadPerson($person);
        }

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('(pr.name IS NOT NULL AND pr.name LIKE :tq)')
                ->orWhere('(seg.name IS NOT NULL AND seg.name LIKE :tq)')
                ->orWhere('(sep.firstName IS NOT NULL AND (sep.firstName LIKE :tq OR sep.lastName LIKE :tq))')
                ->setParameter('tq', '%'.$q.'%');
        }

        // ver siempre las propias
        /** @var Person $user */
        $user = $this->getUser();
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $user);

        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR v.teacher = :teacher')
                ->setParameter('groups', $groups)
                ->setParameter('teacher', $teacher);
        }

        if ($projects) {
            $queryBuilder
                ->andWhere('pr IN (:projects) OR v.teacher = :teacher')
                ->setParameter('projects', $projects)
                ->setParameter('teacher', $teacher);
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

        $title = $translator->trans('title.list', [], 'wlt_visit');

        return $this->render('wlt/visit/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_visit',
            'allow_new' => $allowNew,
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
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

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute('work_linked_training_visit_list');
        }

        $visits = $visitRepository->findAllInListById($items);
        foreach ($visits as $visit) {
            $this->denyAccessUnlessGranted(VisitVoter::MANAGE, $visit);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                foreach ($visits as $visit) {
                    $em->remove($visit);
                }
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_visit'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_visit'));
            }
            return $this->redirectToRoute('work_linked_training_visit_list');
        }

        $title = $translator->trans('title.delete', [], 'wlt_visit');
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
