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

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\WLT\Visit;
use AppBundle\Form\Type\WLT\VisitType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Repository\WLT\VisitRepository;
use AppBundle\Repository\WLT\WLTGroupRepository;
use AppBundle\Security\Edu\EduOrganizationVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\VisitVoter;
use AppBundle\Security\WLT\WLTOrganizationVoter;
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
        WLTGroupRepository $wltGroupRepository
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_CREATE_VISIT, $organization);

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
            $wltGroupRepository,
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
        Visit $visit
    ) {
        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $organization = $userExtensionService->getCurrentOrganization();
        if ($visit->getTeacher()) {
            $academicYear = $visit->getTeacher()->getAcademicYear();
        } else {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $em = $this->getDoctrine()->getManager();

        $readOnly = !$this->isGranted(VisitVoter::MANAGE, $visit);

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $teacher = null;

        if (false === $isManager) {
            $person = $this->getUser()->getPerson();

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
        }
        $teachers = [];
        if (!$isManager && !$isDepartmentHead && $teacher && !$readOnly) {
            $teachers = [$teacher];
        } elseif ($groups) {
            $teachers = $teacherRepository->findByGroups($groups);
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
            $visit->getId() ? 'title.edit' : 'title.new',
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

        $person = $this->getUser()->getPerson();
        if (false === $isWltManager && false === $isManager && false === $isDepartmentHead) {
            // no es administrador ni coordinador de FP ni jefe de familia profesional:
            // puede ser tutor de grupo  -> ver sólo visitas de los
            // estudiantes de sus grupos
            $groups = $groupRepository->findByAcademicYearAndGrupTutorOrDepartmentHeadPerson($academicYear, $person);
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
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $this->getUser()->getPerson());

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

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager->setCurrentPage($page);
        } catch (\PagerFanta\Exception\OutOfRangeCurrentPageException $e) {
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
        if (count($items) === 0) {
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
