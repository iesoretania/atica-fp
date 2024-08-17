<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Controller\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Entity\WltModule\Meeting;
use App\Form\Type\WltModule\MeetingType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WltModule\MeetingRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\TeacherRepository as WltTeacherRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\MeetingVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dual/reunion')]
class MeetingController extends AbstractController
{
    #[Route(path: '/nueva/{academicYear}', name: 'work_linked_training_meeting_new', requirements: ['academicYear' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request              $request,
        TranslatorInterface  $translator,
        UserExtensionService $userExtensionService,
        Security             $security,
        TeacherRepository    $teacherRepository,
        WltTeacherRepository $wltTeacherRepository,
        WltGroupRepository   $groupRepository,
        ManagerRegistry      $managerRegistry,
        AcademicYear         $academicYear = null
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_MEETING, $organization);

        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        /** @var Person $person */
        $person = $this->getUser();
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        $meeting = new Meeting();
        $meeting
            ->setCreatedBy($teacher)
            ->setDateTime(new \DateTime());

        $managerRegistry->getManager()->persist($meeting);

        return $this->index(
            $request,
            $translator,
            $userExtensionService,
            $security,
            $teacherRepository,
            $wltTeacherRepository,
            $groupRepository,
            $managerRegistry,
            $meeting,
            $academicYear
        );
    }

    #[Route(path: '/{id}', name: 'work_linked_training_meeting_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request              $request,
        TranslatorInterface  $translator,
        UserExtensionService $userExtensionService,
        Security             $security,
        TeacherRepository    $teacherRepository,
        WltTeacherRepository $wltTeacherRepository,
        WltGroupRepository   $wltGroupRepository,
        ManagerRegistry      $managerRegistry,
        Meeting              $meeting,
        AcademicYear         $academicYear = null
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $meeting->getCreatedBy()->getAcademicYear();
        }

        $this->denyAccessUnlessGranted(MeetingVoter::ACCESS, $meeting);

        $em = $managerRegistry->getManager();

        $readOnly = !$this->isGranted(MeetingVoter::MANAGE, $meeting);

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);
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
                    $groups = $wltGroupRepository->findByAcademicYearAndWltTeacherPerson($academicYear, $person);
                }
            } else {
                $groups = $wltGroupRepository->findByAcademicYearAndWltTeacherPerson($academicYear, $person);
            }
        }
        $teachers = [];
        if (!$isManager && !$isDepartmentHead && $teacher && !$readOnly) {
            $teachers = [$teacher];
        } elseif ($groups) {
            $teachers = $teacherRepository->findByGroups($groups);
        }

        if (!$teachers) {
            $teachers = $wltTeacherRepository->findByAcademicYear($academicYear);
        }

        $form = $this->createForm(MeetingType::class, $meeting, [
            'disabled' => $readOnly,
            'teachers' => $teachers,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_meeting'));
                return $this->redirectToRoute('work_linked_training_meeting_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_meeting'));
            }
        }

        $title = $translator->trans(
            $meeting->getId() !== null ? 'title.edit' : 'title.new',
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
            'academic_year' => $academicYear,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{academicYear}/{page}', name: 'work_linked_training_meeting_list', requirements: ['academic_year' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                $request,
        UserExtensionService   $userExtensionService,
        TeacherRepository      $teacherRepository,
        WltGroupRepository     $groupRepository,
        ProjectRepository      $projectRepository,
        AcademicYearRepository $academicYearRepository,
        Security               $security,
        TranslatorInterface    $translator,
        ManagerRegistry        $managerRegistry,
        AcademicYear           $academicYear = null,
        int                    $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_MEETING, $organization);
        $allowNew = $this->isGranted(WltOrganizationVoter::WLT_CREATE_MEETING, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('m')
            ->distinct()
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
        $isWltManager = $security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization);

        $groups = [];
        $projects = [];

        /** @var Person $person */
        $person = $this->getUser();
        if (!$isWltManager && !$isManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento o tutor de grupo -> ver solo visitas de los
            // estudiantes de sus grupos
            $groups = $groupRepository->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($academicYear, $person);
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

        // ver siempre las propias
        /** @var Person $user */
        $user = $this->getUser();
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $user);

        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR te = :teacher OR m.createdBy = :teacher')
                ->setParameter('groups', $groups)
                ->setParameter('teacher', $teacher);
        }

        if ($projects) {
            $queryBuilder
                ->andWhere('pr IN (:projects) OR te = :teacher OR m.createdBy = :teacher')
                ->setParameter('projects', $projects)
                ->setParameter('teacher', $teacher);
        }

        if (!$isWltManager && !$isManager && !$projects && !$groups) {
            $queryBuilder
                ->andWhere('m.createdBy = :teacher')
                ->setParameter('teacher', $teacher);
        }

        $queryBuilder
            ->andWhere('cb.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);


        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wlt_meeting');

        return $this->render('wlt/meeting/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_meeting',
            'allow_new' => $allowNew,
            'teacher' => $teacher,
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar', name: 'work_linked_training_meeting_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        MeetingRepository $meetingRepository,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_TEACHER, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('work_linked_training_meeting_list');
        }

        $meetings = $meetingRepository->findByIds($items);
        foreach ($meetings as $meeting) {
            $this->denyAccessUnlessGranted(MeetingVoter::MANAGE, $meeting);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                $meetingRepository->deleteFromList($meetings);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_meeting'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_meeting'));
            }
            return $this->redirectToRoute('work_linked_training_meeting_list');
        }

        $title = $translator->trans('title.delete', [], 'wlt_meeting');
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
