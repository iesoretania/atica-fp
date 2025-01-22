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

namespace App\Controller\ItpModule;

use App\Entity\Edu\ReportTemplate;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\ItpModule\WorkDay;
use App\Entity\Person;
use App\Form\Type\ItpModule\WorkDayTrackingType;
use App\Repository\ItpModule\ActivityRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterActivityRepository;
use App\Repository\ItpModule\WorkDayRepository;
use App\Security\ItpModule\StudentProgramWorkcenterVoter;
use App\Security\ItpModule\WorkDayVoter;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;

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

        $readOnly = !$this->isGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter) &&
            !$this->isGranted(StudentProgramWorkcenterVoter::ATTENDANCE, $studentProgramWorkcenter);

        $workDaysData = $workDayRepository->getCalendarByStudentProgramWorkcenter($studentProgramWorkcenter);

        $today = new \DateTimeImmutable('', new \DateTimeZone('UTC'));
        $today = $today->setTime(0, 0);
        $workDayToday = $workDayRepository->findOneByStudentProgramWorkcenterAndDate($studentProgramWorkcenter, $today);
        $workDayStats = $studentProgramWorkcenter->getWorkDays()->isEmpty()
            ? []
            : $workDayRepository->hoursStatsByStudentProgram($studentProgramWorkcenter);

        $activities = $studentProgramActivityRepository->findByStudentProgramWorkcenterOrderByCode($studentProgramWorkcenter);
        $submittedActivities = $studentProgramActivityRepository->findScaleValueSubmittedByStudentProgramWorkcenter($studentProgramWorkcenter);

        $title = $translator->trans('title.calendar', [], 'itp_tracking');

        $breadcrumb = [
            ['fixed' => $studentProgramWorkcenter->__toString()],
            ['fixed' => $title]
        ];

        $selectable = $this->isGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter) ||
            $this->isGranted(StudentProgramWorkcenterVoter::ATTENDANCE, $studentProgramWorkcenter);

        return $this->render('itp/tracking/calendar.html.twig', [
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

        $date = $workDay->getDate();
        assert($date instanceof \DateTimeInterface);
        $title = $translator->trans('dow' . ($date->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $date->format($translator->trans('format.date', [], 'general'));
        $title .= ' - ' . $translator->trans('caption.hours', ['count' => $workDay->getHours() / 100], 'calendar');

        // precaching
        $activityRepository->findByStudentProgramWorkcenter($studentProgramWorkcenter);

        $lockedActivities = $activityRepository->findDisabledByStudentProgramWorkcenter($studentProgramWorkcenter);
        $previousWorkDay = $workDayRepository->findPrevious($workDay);
        $nextWorkDay = $workDayRepository->findNext($workDay);

        $oldActivities = clone $workDay->getActivities();
        $previousLockStatus = $workDay->isLocked();

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
                    if (!$previousLockStatus) {
                        $workDay->setLocked(true);
                    }
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

        return $this->render('itp/tracking/calendar_form.html.twig', [
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
        if (!$this->isGranted(StudentProgramWorkcenterVoter::LOCK, $studentProgramWorkcenter)
            && !$this->isGranted(StudentProgramWorkcenterVoter::ATTENDANCE, $studentProgramWorkcenter)) {
            throw $this->createAccessDeniedException();
        }
        if ($request->get('week_report')) {
            $year = floor($request->get('week_report') / 100);
            $week = $request->get('week_report') % 100;
            return $this->redirectToRoute(
                'in_company_training_phase_tracking_calendar_activity_report',
                ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId(), 'year' => $year, 'week' => $week]
            );
        }

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

        $title = $translator->trans('title.absence', [], 'itp_tracking');

        $breadcrumb = [
            [
                'fixed' => $studentProgramWorkcenter->__toString(),
                'routeName' => 'in_company_training_phase_tracking_calendar_list',
                'routeParams' => ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/tracking/calendar_attendance.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'student_program_workcenter' => $studentProgramWorkcenter,
            'items' => $workDays
        ]);
    }

    #[Route(path: '/informe/descargar/{studentProgramWorkcenter}/{year}/{week}', name: 'in_company_training_phase_tracking_calendar_activity_report', requirements: ['studentProgramWorkcenter' => '\d+', 'year' => '\d+', 'week' => '\d+'], methods: ['GET'])]
    public function activityReport(
        TranslatorInterface $translator,
        StudentProgramWorkcenter $studentProgramWorkcenter,
        WorkDayRepository $workDayRepository,
        int $year,
        int $week
    ): Response
    {
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::ACCESS, $studentProgramWorkcenter);
        $weekDays = $workDayRepository->findByYearWeekAndStudentProgramWorkcenter($year, $week, $studentProgramWorkcenter);

        if (count($weekDays) === 0) {
            // no hay jornadas, volver al listado
            return $this->redirectToRoute('in_company_training_phase_tracking_calendar_list', ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]);
        }

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);
        ini_set("pcre.backtrack_limit", "5000000");

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            $templateType = $studentProgramWorkcenter->getStudentProgram()?->getProgramGroup()?->getProgramGrade()?->getTrainingProgram()?->getWeeklyActivityReportTemplateType();
            switch ($templateType) {
                case TrainingProgram::WEEK_7_DAYS:
                    $offset1 = 14.5;
                    $offset2 = 14;
                    $offset3 = 7;
                    $weekDayLimit = 8;
                    break;
                default:
                    $offset1 = 0;
                    $offset2 = 0;
                    $offset3 = 0;
                    $weekDayLimit = 6;
            }
            $template = $studentProgramWorkcenter->getStudentProgram()?->getProgramGroup()?->getProgramGrade()?->getTrainingProgram()?->getWeeklyActivityReportTemplate();
            if ($template instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }
            $mpdf->SetFont('DejaVuSansCondensed');
            $mpdf->SetFontSize(9);

            $activities = [];
            $hours = [];
            $notes = [];
            $absence = htmlentities($translator->trans('message.absence', [], 'itp_tracking'));
            $noActivity = htmlentities($translator->trans('message.no_activities', [], 'itp_tracking'));
            $noWorkday = htmlentities($translator->trans('message.no_workday', [], 'itp_tracking'));

            $isLocked = true;

            /** @var WorkDay $workDay */
            foreach ($weekDays as $workDay) {
                if (!$workDay->isLocked()) {
                    $isLocked = false;
                }
                $date = $workDay->getDate();
                assert($date instanceof \DateTimeInterface);
                $day = $date->format('N');
                $activities[$day] = '';
                $hours[$day] = $translator->trans(
                    'form.r_hours',
                    ['count' => $workDay->getHours() / 100.0],
                    'calendar'
                );

                foreach ($workDay->getActivities() as $activity) {
                    if ($activity->getCode() !== '' && $activity->getCode() !== null) {
                        $activities[$day] .= '<b>' . htmlentities((string) $activity->getCode()) . ': </b>';
                    }
                    $activities[$day] .= htmlentities((string) $activity->getName()) . '<br/>';
                }

                if ($workDay->getOtherActivities() !== '' && $workDay->getOtherActivities() !== null) {
                    $activities[$day] .= htmlentities((string) $workDay->getOtherActivities()) . '<br/>';
                }

                if ($workDay->getAbsence() !== WorkDay::ABSENCE_NONE) {
                    $activities[$day] = '<i>' . $absence . '</i>';
                    $hours[$day] = '';
                } elseif ('' === $activities[$day]) {
                    $activities[$day] = '<i>' . $noActivity . '</i>';
                }
                $notes[$day] = $workDay->getNotes();
            }

            $mpdf->AddPage('L');

            // añadir fecha a la ficha
            // obtener primer elemento del array
            $first = $weekDays[0];
            // obtener último elemento del array
            $last = $weekDays[count($weekDays) - 1];

            $firstDate = $first->getDate();
            $lastDate = $last->getDate();
            assert($firstDate instanceof \DateTimeInterface);
            assert($lastDate instanceof \DateTimeInterface);

            $this->pdfWriteFixedPosHTML($mpdf, $firstDate->format('j'), 54.5, 33.5 - $offset1, 8, 5, 'auto', 'center');
            $this->pdfWriteFixedPosHTML($mpdf, $lastDate->format('j'), 67.5, 33.5 - $offset1, 10, 5, 'auto', 'center');
            $this->pdfWriteFixedPosHTML(
                $mpdf,
                $translator->trans(
                    'r_month' . ($lastDate->format('n') - 1),
                    [],
                    'calendar'
                ),
                85,
                33.5 - $offset1,
                23.6,
                5,
                'auto',
                'center'
            );
            $this->pdfWriteFixedPosHTML($mpdf, $lastDate->format('y'), 118.5, 33.5 - $offset1, 6, 5, 'auto', 'center');

            // añadir números de página
            $weekCounter = $workDayRepository->getWeekInformation($first);
            $this->pdfWriteFixedPosHTML($mpdf, $weekCounter['current'], 245.5, 21.9 - $offset3, 6, 5, 'auto', 'center');
            $this->pdfWriteFixedPosHTML($mpdf, $weekCounter['total'], 254.8, 21.9 - $offset3, 6, 5, 'auto', 'center');

            // añadir campos de la cabecera
            $this->pdfWriteFixedPosHTML($mpdf, $studentProgramWorkcenter->getWorkcenter()?->__toString(), 192, 40.8 - $offset1, 72, 5);
            $training = $studentProgramWorkcenter->getStudentProgram()?->getProgramGroup()?->getGroup()?->getGrade()?->getTraining();
            $this->pdfWriteFixedPosHTML($mpdf, $training?->getAcademicYear()?->getOrganization()?->__toString(), 62.7, 40.9 - $offset1, 80, 5);
            $this->pdfWriteFixedPosHTML($mpdf, $studentProgramWorkcenter->getEducationalTutor()?->__toString(), 97.5, 46.5 - $offset1, 46, 5);
            $this->pdfWriteFixedPosHTML($mpdf, $studentProgramWorkcenter->getWorkTutor()?->__toString(), 198, 46.5 - $offset1, 66, 5);
            $this->pdfWriteFixedPosHTML(
                $mpdf,
                $training?->__toString(),
                172,
                54 - $offset1,
                61,
                5
            );
            $studentPerson = $studentProgramWorkcenter->getStudentProgram()?->getStudentEnrollment()?->getPerson();
            assert($studentPerson instanceof Person);

            $this->pdfWriteFixedPosHTML($mpdf, $studentPerson?->__toString(), 63, 54 - $offset1, 80, 5);

            // añadir actividades semanales
            for ($n = 1; $n < $weekDayLimit; $n++) {
                if (isset($activities[$n])) {
                    $activity = $activities[$n];
                    $hour = $hours[$n];
                    $note = $notes[$n];
                } else {
                    $activity = '<i>' . $noWorkday . '</i>';
                    $hour = '';
                    $note = '';
                }
                $this->pdfWriteFixedPosHTML(
                    $mpdf,
                    $activity,
                    58,
                    73.0 + ($n - 1) * 17.8 - $offset1,
                    128,
                    15.8,
                    'auto',
                    'left',
                    false
                );
                $this->pdfWriteFixedPosHTML($mpdf, $hour, 189, 73.0 + ($n - 1) * 17.8 - $offset1, 25, 15.8, 'auto', 'left', false);
                $this->pdfWriteFixedPosHTML($mpdf, $note, 217.5, 73.0 + ($n - 1) * 17.8 - $offset1, 46, 15.8, 'auto', 'justify');
            }

            // añadir pie de firmas
            $this->pdfWriteFixedPosHTML(
                $mpdf,
                $studentPerson->__toString(),
                68,
                185.4 + $offset2,
                53,
                5
            );
            $this->pdfWriteFixedPosHTML($mpdf, $studentProgramWorkcenter->getEducationalTutor()?->__toString() ?? '', 136, 186.9 + $offset2, 53, 5);
            $this->pdfWriteFixedPosHTML($mpdf, $studentProgramWorkcenter->getWorkTutor()?->__toString() ?? '', 204, 184.9 + $offset2, 53, 5);

            // si no está bloqueada la semana, agregar la marca de agua de borrador
            if (!$isLocked) {
                $mpdf->SetWatermarkText($translator->trans('message.draft', [], 'itp_tracking'), 0.1);
                $mpdf->showWatermarkText = true;
                $mpdf->watermark_font = 'DejaVuSansCondensed';
            }

            $title = $translator->trans('title.weekly_activities', [], 'wlt_report')
                . ' - ' . $weekCounter['current'] . ' - ' . $studentProgramWorkcenter->getStudentProgram()?->getStudentEnrollment()?->__toString() . ' - '
                . $studentProgramWorkcenter->getWorkcenter()?->__toString();

            $fileName = $title . '.pdf';

            $mpdf->SetTitle($title);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/pdf');
            $response->setContent($mpdf->Output($fileName, Destination::STRING_RETURN));

            $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

            return $response;
        } finally {
            if ($tmp) {
                unlink($tmp);
            }
        }
    }

    private function pdfWriteFixedPosHTML(
        Mpdf $mpdf,
        ?string $text,
        float|int $x,
        float|int $y,
        int|float $w,
        int|float $h,
        string $overflow = 'auto',
        string $align = 'left',
        bool $escape = true
    ): void {
        if ($escape) {
            $text = nl2br(htmlentities((string) $text));
        }
        $mpdf->WriteFixedPosHTML(
            '<div style="font-family: sans-serif; font-size: 12px; text-align: ' . $align . ';">' . $text . '</div>',
            $x,
            $y,
            $w,
            $h,
            $overflow
        );
    }
}
