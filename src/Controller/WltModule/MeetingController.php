<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WltModule\Meeting;
use App\Form\Type\WltModule\MeetingType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\MeetingRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Security\OrganizationVoter;
use App\Security\WltModule\MeetingVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use App\Service\WltModule\MeetingTeacherProvider;
use Doctrine\Persistence\ManagerRegistry;
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
        TeacherRepository    $teacherRepository,
        MeetingTeacherProvider $teacherProvider,
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

        // solo el profesorado del curso académico puede crear reuniones (el creador es obligatorio)
        if (!$teacher instanceof Teacher) {
            throw $this->createAccessDeniedException();
        }

        $meeting = new Meeting();
        $meeting
            ->setCreatedBy($teacher)
            ->setDateTime(new \DateTime());

        $managerRegistry->getManager()->persist($meeting);

        return $this->index(
            $request,
            $translator,
            $userExtensionService,
            $teacherProvider,
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
        MeetingTeacherProvider $teacherProvider,
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

        $form = $this->createForm(MeetingType::class, $meeting, [
            'disabled' => $readOnly,
            'teachers' => $teacherProvider->forAcademicYear($academicYear, $readOnly),
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
            'meeting' => $meeting,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{academicYear}/{page}', name: 'work_linked_training_meeting_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
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

        $title = $translator->trans('title.list', [], 'wlt_meeting');

        return $this->render('wlt/meeting/list.html.twig', [
            'title' => $title,
            'domain' => 'wlt_meeting',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar', name: 'work_linked_training_meeting_operation', methods: ['POST'])]
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

        if ($request->request->get('confirm', '') === 'ok') {
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
