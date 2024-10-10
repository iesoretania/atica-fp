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

use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\WorkDay;
use App\Form\Model\ItpModule\CalendarAdd;
use App\Form\Type\ItpModule\CalendarAddType;
use App\Form\Type\ItpModule\WorkDayType;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Repository\ItpModule\WorkDayRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/plan/curso/grupo/estudiante/estancia/calendario')]
class StudentProgramWorkcenterCalendarController extends AbstractController
{
    #[Route(path: '/{studentProgramWorkcenter}', name: 'in_company_training_phase_student_program_workcenter_calendar_list', requirements: ['studentProgramWorkcenter' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                            $request,
        TranslatorInterface                $translator,
        WorkDayRepository                  $workDayRepository,
        StudentProgramWorkcenter $studentProgramWorkcenter
    ): Response
    {
        assert($studentProgramWorkcenter instanceof StudentProgramWorkcenter);
        $studentProgram = $studentProgramWorkcenter->getStudentProgram();
        assert($studentProgram instanceof StudentProgram);
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $calendar = $workDayRepository->getCalendarByStudentProgramWorkcenter($studentProgramWorkcenter);

        $title = $translator->trans('title.calendar', [], 'itp_student_program_workcenter');
        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGroup->getProgramGrade()->getId()]
            ],
            [
                'fixed' => $studentProgram->getProgramGroup()->getGroup()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_list',
                'routeParams' => ['programGroup' => $programGroup->getId()]
            ],
            [
                'fixed' => $studentProgram->getStudentEnrollment()->getPerson()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_workcenter_list',
                'routeParams' => ['studentProgram' => $studentProgram->getId()]
            ],
            [
                'fixed' => $studentProgramWorkcenter->getWorkcenter()->__toString()
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/workcenter/calendar_list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'domain' => 'itp_student_program_workcenter',
            'calendar' => $calendar,
            'read_only' => false,
            'student_program_workcenter' => $studentProgramWorkcenter
        ]);
    }

    #[Route(path: '/incorporar/{studentProgramWorkcenter}', name: 'in_company_training_phase_student_program_workcenter_calendar_add', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        TranslatorInterface $translator,
        WorkDayRepository $workDayRepository,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        StudentProgramWorkcenter $studentProgramWorkcenter
    ): Response {
        assert($studentProgramWorkcenter instanceof StudentProgramWorkcenter);
        $studentProgram = $studentProgramWorkcenter->getStudentProgram();
        assert($studentProgram instanceof StudentProgram);
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $title = $translator->trans('title.calendar.add', [], 'itp_student_program_workcenter');

        $calendarAdd = new CalendarAdd();
        $form = $this->createForm(CalendarAddType::class, $calendarAdd);

        $form->handleRequest($request);

        $workDays = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $workDays = $workDayRepository->createWorkDayCollectionByStudentProgramWorkcenterGroupByMonthAndWeekNumber(
                $studentProgramWorkcenter,
                $calendarAdd->getStartDate(),
                $calendarAdd->getTotalHours(),
                [
                    $calendarAdd->getHoursMon(),
                    $calendarAdd->getHoursTue(),
                    $calendarAdd->getHoursWed(),
                    $calendarAdd->getHoursThu(),
                    $calendarAdd->getHoursFri(),
                    $calendarAdd->getHoursSat(),
                    $calendarAdd->getHoursSun()
                ],
                $calendarAdd->getOverwriteAction() === CalendarAdd::OVERWRITE_ACTION_REPLACE,
                $calendarAdd->getIgnoreNonWorkingDays()
            );

            if ('' === $request->get('submit', 'none')) {
                try {
                    $workDayRepository->flush();
                    $studentProgramWorkcenterRepository->updateDates($studentProgramWorkcenter);
                    $this->addFlash('success', $translator->trans('message.added', [], 'calendar'));
                    return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_calendar_list', [
                        'studentProgramWorkcenter' => $studentProgramWorkcenter->getId()
                    ]);
                } catch (\Exception) {
                    $this->addFlash('error', $translator->trans('message.save_error', [], 'calendar'));
                }
            }
        }
        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGroup->getProgramGrade()->getId()]
            ],
            [
                'fixed' => $studentProgram->getProgramGroup()->getGroup()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_list',
                'routeParams' => ['programGroup' => $programGroup->getId()]
            ],
            [
                'fixed' => $studentProgram->getStudentEnrollment()->getPerson()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_workcenter_list',
                'routeParams' => ['studentProgram' => $studentProgram->getId()]
            ],
            [
                'fixed' => $studentProgramWorkcenter->getWorkcenter()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_workcenter_calendar_list',
                'routeParams' => ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/workcenter/calendar_add.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => $title,
            'calendar' => $workDayRepository::groupByMonthAndWeekNumber($workDays)
        ]);
    }

    #[Route(path: '/jornada/{workDay}', name: 'in_company_training_phase_student_program_workcenter_calendar_form', requirements: ['workDay' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                            $request,
        TranslatorInterface                $translator,
        WorkDayRepository                  $workDayRepository,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        WorkDay                            $workDay
    ): Response {
        $studentProgramWorkcenter = $workDay->getStudentProgramWorkcenter();
        assert($studentProgramWorkcenter instanceof StudentProgramWorkcenter);
        $studentProgram = $studentProgramWorkcenter->getStudentProgram();
        assert($studentProgram instanceof StudentProgram);
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $readOnly = false;

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));

        $form = $this->createForm(WorkDayType::class, $workDay, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $workDayRepository->flush();
                $studentProgramWorkcenterRepository->updateDates($studentProgramWorkcenter);
                $this->addFlash('success', $translator->trans('message.saved', [], 'calendar'));
                return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_calendar_list', [
                    'studentProgramWorkcenter' => $studentProgramWorkcenter->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'calendar'));
            }
        }
        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGroup->getProgramGrade()->getId()]
            ],
            [
                'fixed' => $studentProgram->getProgramGroup()->getGroup()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_list',
                'routeParams' => ['programGroup' => $programGroup->getId()]
            ],
            [
                'fixed' => $studentProgram->getStudentEnrollment()->getPerson()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_workcenter_list',
                'routeParams' => ['studentProgram' => $studentProgram->getId()]
            ],
            [
                'fixed' => $studentProgramWorkcenter->getWorkcenter()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_workcenter_calendar_list',
                'routeParams' => ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/workcenter/form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => $title,
            'read_only' => $readOnly
        ]);
    }

    #[Route(path: '/eliminar/{studentProgramWorkcenter}', name: 'in_company_training_phase_student_program_workcenter_calendar_delete', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        TranslatorInterface $translator,
        WorkDayRepository $workDayRepository,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        StudentProgramWorkcenter $studentProgramWorkcenter
    ): Response {
        $studentProgram = $studentProgramWorkcenter->getStudentProgram();
        assert($studentProgram instanceof StudentProgram);
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute(
                'in_company_training_phase_student_program_workcenter_calendar_list',
                ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            );
        }

        $workDays = $workDayRepository->findInListByIdAndStudentProgramWorkcenter($items, $studentProgramWorkcenter);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $workDayRepository->deleteFromList($workDays);

                $workDayRepository->flush();
                $studentProgramWorkcenterRepository->updateDates($studentProgramWorkcenter);
                $this->addFlash('success', $translator->trans('message.deleted', [], 'calendar'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'calendar'));
            }
            return $this->redirectToRoute(
                'in_company_training_phase_student_program_workcenter_calendar_list',
                ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            );
        }

        $title = $translator->trans('title.delete', [], 'calendar');

        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGroup->getProgramGrade()->getId()]
            ],
            [
                'fixed' => $studentProgram->getProgramGroup()->getGroup()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_list',
                'routeParams' => ['programGroup' => $programGroup->getId()]
            ],
            [
                'fixed' => $studentProgram->getStudentEnrollment()->getPerson()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_workcenter_list',
                'routeParams' => ['studentProgram' => $studentProgram->getId()]
            ],
            [
                'fixed' => $studentProgramWorkcenter->getWorkcenter()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_workcenter_calendar_list',
                'routeParams' => ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/workcenter/calendar_delete.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'student_program_workcenter' => $studentProgramWorkcenter,
            'items' => $workDays
        ]);
    }
}
