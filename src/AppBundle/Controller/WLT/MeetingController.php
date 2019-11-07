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
use AppBundle\Entity\WLT\Meeting;
use AppBundle\Form\Type\WLT\MeetingType;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WLT\MeetingRepository;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Repository\WLT\WLTGroupRepository;
use AppBundle\Security\OrganizationVoter;
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
 * @Route("/dual/reunion")
 */
class MeetingController extends Controller
{
    /**
     * @Route("/nueva", name="work_linked_training_meeting_new", methods={"GET", "POST"})
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
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_TEACHER, $organization);

        $academicYear = $organization->getCurrentAcademicYear();

        $person = $this->getUser()->getPerson();
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        $meeting = new Meeting();
        $meeting
            ->setProject($academicYear)
            ->setCreatedBy($teacher)
            ->setDateTime(new \DateTime());

        $this->getDoctrine()->getManager()->persist($meeting);

        return $this->indexAction(
            $request,
            $translator,
            $userExtensionService,
            $security,
            $teacherRepository,
            $groupRepository,
            $meeting
        );
    }

    /**
     * @Route("/{id}", name="work_linked_training_meeting_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        Meeting $meeting
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_TEACHER, $organization);

        $em = $this->getDoctrine()->getManager();

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization)
                     || $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        $groups = [];
        if (false === $isManager) {
            $person = $this->getUser()->getPerson();
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor
            $teacher =
                $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($teacher) {
                $groups = $groupRepository->findByAcademicYearAndTeacher($academicYear, $teacher);
            }

            $readOnly = $meeting->getCreatedBy() !== $teacher;
        } else {
            $readOnly = false;
        }

        $form = $this->createForm(MeetingType::class, $meeting, [
            'disabled' => $readOnly,
            'is_manager' => $isManager,
            'groups' => $groups
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_meeting'));
                return $this->redirectToRoute('work_linked_training_meeting_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_meeting'));
            }
        }

        $title = $translator->trans(
            $meeting->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_meeting'
        );

        $breadcrumb = [
                ['fixed' => $title]
        ];

        return $this->render('wlt/meeting/form.html.twig', [
            'menu_path' => 'work_linked_training_meeting_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_meeting_list",
     *     requirements={"academic_year" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        MeetingRepository $meetingRepository,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $groupRepository,
        ProjectRepository $projectRepository,
        Security $security,
        TranslatorInterface $translator,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_MEETING, $organization);
        $allowNew = $this->isGranted(WLTOrganizationVoter::WLT_CREATE_MEETING, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('m')
            ->distinct(true)
            ->addSelect('pr')
            ->addSelect('cb')
            ->addSelect('p')
            ->from(Meeting::class, 'm')
            ->join('m.project', 'pr')
            ->join('m.createdBy', 'cb')
            ->join('cb.person', 'p')
            ->leftJoin('m.teachers', 'te')
            ->leftJoin('te.person', 'tep')
            ->leftJoin('m.studentEnrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->addOrderBy('m.dateTime', 'DESC');

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        $groups = [];
        $projects = [];

        $person = $this->getUser()->getPerson();
        if (false === $isWltManager && false === $isManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento o tutor de grupo  -> ver sólo visitas de los
            // estudiantes de sus grupos
            $groups = $groupRepository->findByAcademicYearAndGrupTutorOrDepartmentHeadPerson($academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $projectRepository->findByManager($person);
        }

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('(pr.name IS NOT NULL AND pr.name LIKE :tq)')
                ->orWhere('(sep.firstName IS NOT NULL AND (sep.firstName LIKE :tq OR sep.lastName LIKE :tq))')
                ->orWhere('(tep.firstName IS NOT NULL AND (tep.firstName LIKE :tq OR tep.lastName LIKE :tq))')
                ->setParameter('tq', '%'.$q.'%');
        }

        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups)')
                ->setParameter('groups', $groups);
        }

        if ($projects) {
            $queryBuilder
                ->andWhere('pr IN (:projects)')
                ->setParameter('projects', $projects);
        }

        // ver siempre las propias
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $this->getUser()->getPerson());

        if ($teacher) {
            $queryBuilder
                ->orWhere('v.teacher = :teacher')
                ->setParameter('teacher', $teacher);
        }

        $queryBuilder
            ->andWhere('cb.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);


        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.list', [], 'wlt_meeting');

        return $this->render('wlt/meeting/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_meeting',
            'allow_new' => $allowNew,
            'teacher' => $teacher,
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/eliminar", name="work_linked_training_meeting_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        MeetingRepository $meetingRepository,
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $organization->getCurrentAcademicYear();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_TEACHER, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('work_linked_training_meeting_list');
        }

        $person = $this->getUser()->getPerson();
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        $meetings = $meetingRepository->findAllInListByIdAndAcademicYearAndTeacher($items, $academicYear, $teacher);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $meetingRepository->deleteFromList($meetings);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_meeting'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_meeting'));
            }
            return $this->redirectToRoute('work_linked_training_meeting_list');
        }

        $title = $this->get('translator')->trans('title.delete', [], 'wlt_meeting');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/meeting/delete.html.twig', [
            'menu_path' => 'work_linked_training_meeting_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $meetings
        ]);
    }
}
