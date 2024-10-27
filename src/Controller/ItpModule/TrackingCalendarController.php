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

namespace App\Controller\ItpModule;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\WorkDay;
use App\Form\Type\ItpModule\WorkDayTrackingType;
use App\Repository\ItpModule\ActivityRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterActivityRepository;
use App\Repository\ItpModule\WorkDayRepository;
use App\Security\ItpModule\StudentProgramWorkcenterVoter;
use App\Security\ItpModule\WorkDayVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/seguimiento/calendario')]
class TrackingCalendarController extends AbstractController
{
    #[Route(path: '/{studentProgramWorkcenter}', name: 'in_company_training_phase_tracking_calendar_list', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['GET'])]
    public function index(
        WorkDayRepository                          $workDayRepository,
        StudentProgramWorkcenterActivityRepository $studentProgramActivityRepository,
        TranslatorInterface                        $translator,
        StudentProgramWorkcenter                   $studentProgramWorkcenter
    ): Response
    {
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::ACCESS, $studentProgramWorkcenter);

        $readOnly = !$this->isGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter);

        $workDaysData = $workDayRepository->getCalendarByStudentProgramWorkcenter($studentProgramWorkcenter);

        $today = new \DateTimeImmutable('', new \DateTimeZone('UTC'));
        $today = $today->setTime(0, 0);
        $workDayToday = $workDayRepository->findOneByStudentProgramWorkcenterAndDate($studentProgramWorkcenter, $today);
        $workDayStats = $studentProgramWorkcenter->getWorkDays()->isEmpty()
            ? []
            : $workDayRepository->hoursStatsByStudentProgram($studentProgramWorkcenter);

        $activities = $studentProgramActivityRepository->findByStudentProgramWorkcenterOrderByCode($studentProgramWorkcenter);
        $submittedActivities = $studentProgramActivityRepository->findSubmittedByStudentProgramWorkcenter($studentProgramWorkcenter);

        $title = $translator->trans('title.calendar', [], 'itp_tracking');

        $breadcrumb = [
            ['fixed' => $studentProgramWorkcenter->__toString()],
            ['fixed' => $title]
        ];

        $selectable = $this->isGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter) ||
            $this->isGranted(StudentProgramWorkcenterVoter::ATTENDANCE, $studentProgramWorkcenter);

        return $this->render('itp/training_program/tracking/calendar.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'show_title' => false,
            'student_program_workcenter' => $studentProgramWorkcenter,
            'selectable' => $selectable,
            'activities' => $activities,
            'submitted_activities' => $submittedActivities,
            'work_day_stats' => $workDayStats,
            'work_day_today' => $workDayToday,
            'calendar' => $workDaysData,
            'read_only' => $readOnly
        ]);
    }

    #[Route(path: '/jornada/{workDay}', name: 'in_company_training_phase_tracking_calendar_form', requirements: ['workDay' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                                    $request,
        TranslatorInterface                        $translator,
        WorkDayRepository                          $workDayRepository,
        StudentProgramWorkcenterActivityRepository $studentProgramWorkcenterActivityRepository,
        ActivityRepository                         $activityRepository,
        WorkDay                                    $workDay
    ): Response
    {
        $studentProgramWorkcenter = $workDay->getStudentProgramWorkcenter();
        assert($studentProgramWorkcenter instanceof StudentProgramWorkcenter);
        $this->denyAccessUnlessGranted(WorkDayVoter::ACCESS, $workDay);
        $readOnly = !$this->isGranted(WorkDayVoter::FILL, $workDay);

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));
        $title .= ' - ' . $translator->trans('caption.hours', ['count' => $workDay->getHours()], 'calendar');

        // precaching
        $activityRepository->findByStudentProgramWorkcenter($studentProgramWorkcenter);

        $lockedActivities = $activityRepository->findDisabledByStudentProgramWorkcenter($studentProgramWorkcenter);
        $previousWorkDay = $workDayRepository->findPrevious($workDay);
        $nextWorkDay = $workDayRepository->findNext($workDay);

        $oldActivities = clone $workDay->getActivities();

        $form = $this->createForm(WorkDayTrackingType::class, $workDay, [
            'work_day' => $workDay,
            'locked_activities' => $lockedActivities,
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($workDay->getAbsence() === WorkDay::ABSENCE_NONE) {
                    $lockManager = $this->isGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter);
                    $currentActivities = $workDay->getActivities();

                    $toInsert = [];
                    foreach ($currentActivities as $activity) {
                        if (!$oldActivities->contains($activity)) {
                            $toInsert[] = $activity;
                        }
                    }

                    // comprobar que no se intenta activar una concreción ya bloqueada
                    $invalid = [];
                    foreach ($toInsert as $activity) {
                        if (in_array($activity, $lockedActivities, true)) {
                            $invalid[] = $activity;
                        }
                    }
                    if (!$lockManager && $invalid !== []) {
                        throw $this->createAccessDeniedException();
                    }

                    // asegurar que no se pierden las concreciones marcadas pero bloqueadas
                    foreach ($lockedActivities as $activity) {
                        if ($oldActivities->contains($activity) && !$workDay->getActivities()->contains($activity)) {
                            $workDay->getActivities()->add($activity);
                        }
                    }
                } else {
                    $workDay->getActivities()->clear();
                    $workDay->setOtherActivities(null);
                }
                $workDayRepository->flush();
                $this->addFlash('success', $translator->trans('message.workday_saved', [], 'itp_tracking'));
                return $this->redirectToRoute('in_company_training_phase_tracking_calendar_list', [
                    'studentProgramWorkcenter' => $studentProgramWorkcenter->getId()
                ]);
            } catch (AccessDeniedException $e) {
                throw $e;
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.workday_save_error', [], 'itp_tracking'));
            }
        }

        $breadcrumb = [
            [
                'fixed' => $studentProgramWorkcenter->__toString(),
                'routeName' => 'in_company_training_phase_tracking_calendar_list',
                'routeParams' => ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/tracking/calendar_form.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'read_only' => $readOnly,
            'work_day' => $workDay,
            'previous_work_day' => $previousWorkDay,
            'next_work_day' => $nextWorkDay,
            'title' => $title
        ]);
    }

    #[Route(path: '/{id}/operacion', name: 'in_company_training_phase_tracking_calendar_operation', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        WorkDayRepository $workDayRepository,
        TranslatorInterface $translator,
        StudentProgramWorkcenter $studentProgramWorkcenter
    ): Response
    {
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::ACCESS, $studentProgramWorkcenter);
        /*if ($request->get('week_report')) {
            $year = floor($request->get('week_report') / 100);
            $week = $request->get('week_report') % 100;
            return $this->redirectToRoute(
                'work_linked_training_tracking_calendar_activity_report',
                ['id' => $agreement->getId(), 'year' => $year, 'week' => $week]
            );
        }*/

        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter);

        if ($request->get('lock_week')) {
            $year = floor($request->get('lock_week') / 100);
            $week = $request->get('lock_week') % 100;
            $workDayRepository->updateWeekLock($year, $week, $studentProgramWorkcenter, true);
            return $this->redirectToRoute(
                'in_company_training_phase_tracking_calendar_list',
                ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            );
        }

        if ($request->get('unlock_week')) {
            $year = floor($request->get('unlock_week') / 100);
            $week = $request->get('unlock_week') % 100;
            $workDayRepository->updateWeekLock($year, $week, $studentProgramWorkcenter, false);
            return $this->redirectToRoute(
                'in_company_training_phase_tracking_calendar_list',
                ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            );
        }

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute(
                'in_company_training_phase_tracking_calendar_list',
                ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            );
        }

        $workDays = $workDayRepository->findInListByIdAndStudentProgramWorkcenter($items, $studentProgramWorkcenter);

        // comprobar si es bloqueo de jornadas
        $locked = $request->get('lock') === '';
        if ($locked || $request->get('unlock') === '') {
            try {
                $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter);
                $workDayRepository->updateLock($workDays, $studentProgramWorkcenter, $locked);
                $workDayRepository->flush();
                $this->addFlash('success', $translator->trans('message.locked', [], 'itp_tracking'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.locked_error', [], 'itp_tracking'));
            }
            return $this->redirectToRoute(
                'in_company_training_phase_tracking_calendar_list',
                ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            );
        }

        // marcar en las jornadas que estudiante no ha estado en el centro de trabajo
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::ATTENDANCE, $studentProgramWorkcenter);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $workDayRepository->updateAttendance($workDays, $studentProgramWorkcenter, WorkDay::ABSENCE_UNJUSTIFIED);
                $workDayRepository->flush();
                $this->addFlash('success', $translator->trans('message.attendance_updated', [], 'itp_tracking'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.attendance_error', [], 'itp_tracking'));
            }
            return $this->redirectToRoute(
                'in_company_training_phase_tracking_calendar_list',
                ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            );
        }

        $title = $translator->trans('title.attendance', [], 'calendar');

        $breadcrumb = [
            [
                'fixed' => $studentProgramWorkcenter->__toString(),
                'routeName' => 'in_company_training_phase_tracking_calendar_list',
                'routeParams' => ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/tracking/calendar_attendance.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'student_program_workcenter' => $studentProgramWorkcenter,
            'items' => $workDays
        ]);
    }
}
