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

namespace App\Controller\WPT;

use App\Entity\Edu\ReportTemplate;
use App\Entity\WPT\Activity;
use App\Entity\WPT\ActivityTracking;
use App\Entity\WPT\AgreementEnrollment;
use App\Entity\WPT\TrackedWorkDay;
use App\Entity\WPT\WorkDay;
use App\Form\Type\WPT\WorkDayTrackingType;
use App\Repository\WPT\ActivityRepository;
use App\Repository\WPT\ActivityTrackingRepository;
use App\Repository\WPT\AgreementEnrollmentRepository;
use App\Repository\WPT\AgreementRepository;
use App\Repository\WPT\TrackedWorkDayRepository;
use App\Repository\WPT\WorkDayRepository;
use App\Repository\WPT\WPTTeacherRepository;
use App\Security\WPT\AgreementEnrollmentVoter;
use App\Security\WPT\TrackedWorkDayVoter;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

#[Route(path: '/fct/seguimiento/calendario')]
class TrackingCalendarController extends AbstractController
{
    #[Route(path: '/{id}', name: 'workplace_training_tracking_calendar_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(
        TrackedWorkDayRepository $trackedWorkDayRepository,
        WorkDayRepository $workDayRepository,
        TranslatorInterface $translator,
        ActivityRepository $activityRepository,
        ActivityTrackingRepository $activityTrackingRepository,
        AgreementEnrollment $agreementEnrollment
    ): Response
    {
        $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::ACCESS, $agreementEnrollment);

        $readOnly = !$this->isGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment);

        $workDaysData = $trackedWorkDayRepository
            ->findByAgreementEnrollmentGroupByMonthAndWeekNumber($agreementEnrollment);

        $agreement = $agreementEnrollment->getAgreement();
        $activityStats = $activityRepository->getProgramActivitiesStatsFromAgreementEnrollment($agreementEnrollment);
        $activityTotalCount = $activityTrackingRepository->getCountFromAgreementEnrollment($agreementEnrollment);
        $activityTrackedCount = $activityTrackingRepository
            ->getTrackedCountFromAgreementEnrollment($agreementEnrollment);
        $activityTotalHours = $activityTrackingRepository->getTotalHoursFromAgreementEnrollment($agreementEnrollment);

        $today = new \DateTime('', new \DateTimeZone('UTC'));
        $today->setTime(0, 0);
        $workDayToday = $workDayRepository->findOneByAgreementAndDate($agreement, $today);

        $workDayStats = count($agreement->getWorkDays()) > 0
            ? $trackedWorkDayRepository->hoursStatsByAgreementEnrollment($agreementEnrollment)
            : [];

        $workDayRealStats = count($agreement->getWorkDays()) > 0
            ? $trackedWorkDayRepository->realHoursByAgreementEnrollment($agreementEnrollment) / 100
            : 0;

        $title = $translator->trans('title.calendar', [], 'wpt_tracking');

        $breadcrumb = [
            ['fixed' => $agreementEnrollment->__toString()],
            ['fixed' => $title]
        ];

        $selectable = $this->isGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment) ||
            $this->isGranted(AgreementEnrollmentVoter::ATTENDANCE, $agreementEnrollment);

        $backUrl = $this->generateUrl('workplace_training_tracking_list', [
            'academicYear' => $agreementEnrollment
                ->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear()->getId()
        ]);

        return $this->render('wpt/tracking/calendar.html.twig', [
            'menu_path' => 'workplace_training_tracking_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'show_title' => false,
            'agreement_enrollment' => $agreementEnrollment,
            'selectable' => $selectable,
            'work_day_stats' => $workDayStats,
            'work_day_today' => $workDayToday,
            'work_day_real_stats' => $workDayRealStats,
            'activity_stats' => $activityStats,
            'activity_tracked_count' => $activityTrackedCount,
            'activity_total_count' => $activityTotalCount,
            'activity_total_hours' => $activityTotalHours,
            'calendar' => $workDaysData,
            'read_only' => $readOnly,
            'back_url' => $backUrl
        ]);
    }

    #[Route(path: '/jornada/{workDay}/{agreementEnrollment}', name: 'workplace_training_tracking_calendar_form', requirements: ['workDay' => '\d+', 'agreementEnrollment' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        WorkDayRepository $workDayRepository,
        ManagerRegistry $managerRegistry,
        WorkDay $workDay,
        AgreementEnrollment $agreementEnrollment
    ): Response
    {
        $trackedWorkDay = $trackedWorkDayRepository->findOneOrNewByWorkDayAndAgreementEnrollment(
            $workDay,
            $agreementEnrollment
        );

        if (!$trackedWorkDay instanceof TrackedWorkDay) {
            throw $this->createAccessDeniedException();
        }

        $agreement = $workDay->getAgreement();
        $this->denyAccessUnlessGranted(TrackedWorkDayVoter::ACCESS, $trackedWorkDay);
        $readOnly = !$this->isGranted(TrackedWorkDayVoter::FILL, $trackedWorkDay);

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));
        $title .= ' - ' . $translator->trans('caption.hours', ['count' => $workDay->getHours()], 'calendar');

        $previousWorkDay = $workDayRepository->findPrevious($workDay);
        $nextWorkDay = $workDayRepository->findNext($workDay);

        $trackedActivities = $trackedWorkDay->getTrackedActivities();
        $activities = clone $agreementEnrollment->getActivities();

        foreach ($trackedActivities as $trackedActivity) {
            $trackedWorkDay->getTrackedActivities()->removeElement($trackedActivity->getActivity());
        }
        foreach ($activities as $newActivity) {
            $newTrackedActivity = new ActivityTracking();
            $newTrackedActivity
                ->setActivity($newActivity)
                ->setTrackedWorkDay($trackedWorkDay)
                ->setHours(0);
            $trackedWorkDay->getTrackedActivities()->add($newTrackedActivity);
        }

        $form = $this->createForm(WorkDayTrackingType::class, $trackedWorkDay, [
            'work_day' => $trackedWorkDay,
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $trackedActivities = $trackedWorkDay->getTrackedActivities();
                if ($trackedWorkDay->getAbsence() !== TrackedWorkDay::NO_ABSENCE) {
                    $trackedActivities->clear();
                } else {
                    foreach ($trackedActivities as $trackedActivity) {
                        if ($trackedActivity->getHours() == 0) {
                            $trackedActivities->removeElement($trackedActivity);
                            if ($trackedWorkDay->getId() !== null) {
                                $managerRegistry->getManager()->remove($trackedActivity);
                            }
                        } else {
                            $managerRegistry->getManager()->persist($trackedActivity);
                        }
                    }
                }
                $trackedWorkDay->setTrackedActivities($trackedActivities);
                $managerRegistry->getManager()->flush();

                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.workday_saved', [], 'calendar'));
                return $this->redirectToRoute('workplace_training_tracking_calendar_list', [
                    'id' => $agreementEnrollment->getId()
                ]);
            } catch (AccessDeniedException $e) {
                throw $e;
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.workday_save_error', [], 'calendar'));
            }
        }

        $breadcrumb = [
            [
                'fixed' => (string)$agreement,
                'routeName' => 'workplace_training_tracking_calendar_list',
                'routeParams' => ['id' => $agreementEnrollment->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/tracking/calendar_form.html.twig', [
            'menu_path' => 'workplace_training_tracking_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'read_only' => $readOnly,
            'tracked_work_day' => $trackedWorkDay,
            'previous_work_day' => $previousWorkDay,
            'next_work_day' => $nextWorkDay,
            'title' => $title
        ]);
    }

    #[Route(path: '/{id}/operacion', name: 'workplace_training_tracking_calendar_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AgreementEnrollment $agreementEnrollment
    ): Response
    {
        $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::ACCESS, $agreementEnrollment);
        if ($request->get('week_report')) {
            $year = floor($request->get('week_report') / 100);
            $week = $request->get('week_report') % 100;
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_activity_report',
                ['id' => $agreementEnrollment->getId(), 'year' => $year, 'week' => $week]
            );
        }

        $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment);

        if ($request->get('lock_week')) {
            $year = floor($request->get('lock_week') / 100);
            $week = $request->get('lock_week') % 100;
            $trackedWorkDayRepository->updateWeekLock($year, $week, $agreementEnrollment, true);
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_list',
                ['id' => $agreementEnrollment->getId()]
            );
        }

        if ($request->get('unlock_week')) {
            $year = floor($request->get('unlock_week') / 100);
            $week = $request->get('unlock_week') % 100;
            $trackedWorkDayRepository->updateWeekLock($year, $week, $agreementEnrollment, false);
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_list',
                ['id' => $agreementEnrollment->getId()]
            );
        }

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_list',
                ['id' => $agreementEnrollment->getId()]
            );
        }

        $trackedWorkDays = $trackedWorkDayRepository->
        findInListByWorkDayIdAndAgreementEnrollment($items, $agreementEnrollment);

        // comprobar si es bloqueo de jornadas
        $locked = $request->get('lock') === '';
        if ($locked || $request->get('unlock') === '') {
            $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment);
            try {
                $trackedWorkDayRepository->updateLock($trackedWorkDays, $agreementEnrollment, $locked);
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.locked', [], 'calendar'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.locked_error', [], 'calendar'));
            }
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_list',
                ['id' => $agreementEnrollment->getId()]
            );
        }

        // marcar en las jornadas que estudiante no ha estado en el centro de trabajo
        $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::ATTENDANCE, $agreementEnrollment);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $trackedWorkDayRepository->updateAttendance($trackedWorkDays, true);
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.attendance_updated', [], 'calendar'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.attendance_error', [], 'calendar'));
            }
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_list',
                ['id' => $agreementEnrollment->getId()]
            );
        }

        $title = $translator->trans('title.attendance', [], 'calendar');

        $breadcrumb = [
            [
                'fixed' => (string)$agreementEnrollment,
                'routeName' => 'workplace_training_tracking_calendar_list',
                'routeParams' => ['id' => $agreementEnrollment->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/tracking/calendar_attendance.html.twig', [
            'menu_path' => 'workplace_training_tracking_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreementEnrollment,
            'items' => $trackedWorkDays
        ]);
    }

    #[IsGranted(AgreementEnrollmentVoter::ACCESS, subject: 'agreementEnrollment')]
    #[Route(path: '/{id}/asistencia/descargar', name: 'workplace_training_tracking_calendar_attendance_report', methods: ['GET'])]
    public function attendanceReport(
        Environment $engine,
        TranslatorInterface $translator,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        AgreementEnrollment $agreementEnrollment
    ) {
        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);
        ini_set("pcre.backtrack_limit", "5000000");

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            $template = $agreementEnrollment->getAgreement()->getShift()->getAttendanceReportTemplate();
            if ($template instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }

            $title = $translator->trans('title.attendance', [], 'wpt_report')
                . ' - ' . $agreementEnrollment->getStudentEnrollment() . ' - '
                . $agreementEnrollment->getAgreement()->getWorkcenter();

            $fileName = $title . '.pdf';

            $workDays = $trackedWorkDayRepository->findByAgreementEnrollmentWithWorkDay($agreementEnrollment);

            $html = $engine->render('wpt/tracking/attendance_report.html.twig', [
                'agreement' => $agreementEnrollment->getAgreement(),
                'agreement_enrollment' => $agreementEnrollment,
                'workdays' => $workDays,
                'title' => $title
            ]);

            $response = $mpdfService->generatePdfResponse(
                $html,
                ['mpdf' => $mpdf]
            );
            $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

            return $response;
        } finally {
            if ($tmp) {
                unlink($tmp);
            }
        }
    }

    #[IsGranted(AgreementEnrollmentVoter::VIEW_ACTIVITY_REPORT, subject: 'agreementEnrollment')]
    #[Route(path: '/{id}/actividades/descargar', name: 'workplace_training_tracking_calendar_activity_summary_report', methods: ['GET'])]
    public function activitySummaryReport(
        Environment $engine,
        TranslatorInterface $translator,
        AgreementEnrollmentRepository $agreementEnrollmentRepository,
        ActivityTrackingRepository $activityTrackingRepository,
        ActivityRepository $activityRepository,
        WPTTeacherRepository $wptTeacherRepository,
        WorkDayRepository $workDayRepository,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        AgreementEnrollment $agreementEnrollment
    ) {
        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);
        ini_set("pcre.backtrack_limit", "5000000");

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        $totalHours = 0;

        try {
            $template = $agreementEnrollment->getAgreement()->getShift()->getActivitySummaryReportTemplate();
            if ($template instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }

            $title = $translator->trans('title.report', [], 'wpt_activity_report')
                . ' - ' . $agreementEnrollment->getStudentEnrollment();

            $fileName = $title . '.pdf';

            $agreementEnrollments = $agreementEnrollmentRepository
                ->findByStudentEnrollmentAndShift(
                    $agreementEnrollment->getStudentEnrollment(),
                    $agreementEnrollment->getAgreement()->getShift()
                );

            $data = [];
            foreach ($agreementEnrollments as $agreementEnrollment2) {
                $item = [];
                $item[0] = $agreementEnrollment2;
                $item[1] = $activityRepository->getProgramActivitiesStatsFromAgreementEnrollment($agreementEnrollment2);
                $item[2] = $trackedWorkDayRepository->getOtherActivitiesFromAgreementEnrollment($agreementEnrollment2);
                $data[] = $item;

                $totalHours += $workDayRepository->getTotalHoursByAgreement(
                    $agreementEnrollment2->getAgreement()
                );
            }
            $shift = $agreementEnrollment->getAgreement()->getShift();
            $total = $activityTrackingRepository->getTrackedHoursFromStudentEnrollment(
                $agreementEnrollment->getStudentEnrollment()
            );
            $educationalTutors = $wptTeacherRepository->findEducationalTutorsByStudentEnrollmentAndShift(
                $agreementEnrollment->getStudentEnrollment(),
                $agreementEnrollment->getAgreement()->getShift()
            );

            $html = $engine->render('wpt/tracking/activity_report.html.twig', [
                'agreement' => $agreementEnrollment->getAgreement(),
                'student_enrollment' => $agreementEnrollment->getStudentEnrollment(),
                'data' => $data,
                'shift' => $shift,
                'total_hours' => $totalHours,
                'total' => $total,
                'educational_tutors' => $educationalTutors,
                'document_date' => $agreementEnrollment->getAgreement()->getEndDate(),
                'title' => $title
            ]);

            $response = $mpdfService->generatePdfResponse(
                $html,
                ['mpdf' => $mpdf]
            );
            $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

            return $response;
        } finally {
            if ($tmp) {
                unlink($tmp);
            }
        }
    }

    #[IsGranted(AgreementEnrollmentVoter::ACCESS, subject: 'agreementEnrollment')]
    #[Route(path: '/{id}/informe/{year}/{week}', name: 'workplace_training_tracking_calendar_activity_report', methods: ['GET'])]
    public function activityReport(
        TranslatorInterface $translator,
        AgreementEnrollment $agreementEnrollment,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        $year,
        $week
    ): Response
    {
        $weekDays = $trackedWorkDayRepository->findByYearWeekAndAgreementEnrollment($year, $week, $agreementEnrollment);

        if ((is_countable($weekDays) ? count($weekDays) : 0) === 0) {
            // no hay jornadas, volver al listado
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_list',
                ['id' => $agreementEnrollment->getId()]
            );
        }

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);
        ini_set("pcre.backtrack_limit", "5000000");

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            if ($agreementEnrollment->getAgreement()->getShift()->getWeeklyActivityReportTemplate() instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents(
                    $tmp,
                    $agreementEnrollment->getAgreement()->getShift()->getWeeklyActivityReportTemplate()->getData()
                );
                $mpdf->SetDocTemplate($tmp, true);
            }
            $mpdf->SetFont('DejaVuSansCondensed');
            $mpdf->SetFontSize(9);

            $activities = [];
            $hours = [];
            $notes = [];
            $noActivity = htmlentities($translator->trans('form.no_activities', [], 'calendar'));
            $noWorkday = htmlentities($translator->trans('form.no_workday', [], 'calendar'));

            $isLocked = true;

            /** @var TrackedWorkDay $trackedWorkDay */
            foreach ($weekDays as $trackedWorkDay) {
                if (!$trackedWorkDay->isLocked()) {
                    $isLocked = false;
                }
                $day = $trackedWorkDay->getWorkDay()->getDate()->format('N');
                $activities[$day] = '';
                $hours[$day] = '';

                foreach ($trackedWorkDay->getTrackedActivities() as $trackedActivity) {
                    if ($trackedActivity->getActivity()->getCode() !== '' && $trackedActivity->getActivity()->getCode(
                        ) !== null) {
                        $activities[$day] .= '<b>' .
                            htmlentities((string) $trackedActivity->getActivity()->getCode()) . ': </b>';
                    }
                    $activities[$day] .= htmlentities((string) $trackedActivity->getActivity()->getDescription()) . '<br/>';
                    if ($hours[$day] === '') {
                        $hours[$day] =
                            '<ul style="padding: 0; margin: 0; list-style: square inside;">';
                    }
                    $hours[$day] .= '<li>' . $translator->trans(
                            'form.r_hours',
                            ['count' => $trackedActivity->getHours() / 100.0],
                            'calendar'
                        );
                }
                if ($hours[$day] !== '') {
                    $hours[$day] .= '</ul>';
                }

                if ($trackedWorkDay->getOtherActivities() !== '' && $trackedWorkDay->getOtherActivities() !== null) {
                    $activities[$day] .= htmlentities((string) $trackedWorkDay->getOtherActivities()) . '<br/>';
                }

                if ('' === $activities[$day]) {
                    $activities[$day] = '<i>' . $noActivity . '</i>';
                }
                $notes[$day] = $trackedWorkDay->getNotes();
            }

            $mpdf->AddPage('L');

            // añadir fecha a la ficha
            $first = reset($weekDays);
            $last = end($weekDays);

            self::pdfWriteFixedPosHTML(
                $mpdf,
                $first->getWorkDay()->getDate()->format('j'),
                54.5,
                33.5,
                8,
                5,
                'auto',
                'center'
            );
            self::pdfWriteFixedPosHTML(
                $mpdf,
                $last->getWorkDay()->getDate()->format('j'),
                67.5,
                33.5,
                10,
                5,
                'auto',
                'center'
            );
            self::pdfWriteFixedPosHTML(
                $mpdf,
                $translator->trans(
                    'r_month' . ($last->getWorkDay()->getDate()->format('n') - 1),
                    [],
                    'calendar'
                ),
                85,
                33.5,
                23.6,
                5,
                'auto',
                'center'
            );
            self::pdfWriteFixedPosHTML(
                $mpdf,
                $last->getWorkDay()->getDate()->format('y'),
                118.5,
                33.5,
                6,
                5,
                'auto',
                'center'
            );

            // añadir números de página
            $weekCounter = $trackedWorkDayRepository->getWeekInformation($first);
            self::pdfWriteFixedPosHTML($mpdf, $weekCounter['current'], 245.5, 21.9, 6, 5, 'auto', 'center');
            self::pdfWriteFixedPosHTML($mpdf, $weekCounter['total'], 254.8, 21.9, 6, 5, 'auto', 'center');

            // añadir campos de la cabecera
            self::pdfWriteFixedPosHTML(
                $mpdf,
                (string)$agreementEnrollment->getAgreement()->getWorkcenter(),
                192,
                40.8,
                72,
                5
            );
            self::pdfWriteFixedPosHTML(
                $mpdf,
                $agreementEnrollment->getAgreement()->getShift()->getGrade()->getTraining()->getAcademicYear(
                )->getOrganization(),
                62.7,
                40.9,
                80,
                5,
                'auto',
                'left'
            );
            self::pdfWriteFixedPosHTML($mpdf, (string)$agreementEnrollment->getEducationalTutor(), 97.5, 46.5, 46, 5);
            self::pdfWriteFixedPosHTML($mpdf, (string)$agreementEnrollment->getWorkTutor(), 198, 46.5, 66, 5);
            self::pdfWriteFixedPosHTML(
                $mpdf,
                (string)$agreementEnrollment->getStudentEnrollment()->getGroup()->getGrade()->getTraining(),
                172,
                54,
                61,
                5
            );
            self::pdfWriteFixedPosHTML(
                $mpdf,
                $agreementEnrollment->getAgreement()->getShift()->getType(),
                244,
                54,
                20,
                5
            );
            self::pdfWriteFixedPosHTML(
                $mpdf,
                (string)$agreementEnrollment->getStudentEnrollment()->getPerson(),
                63,
                54,
                80,
                5
            );

            // añadir actividades semanales
            for ($n = 1; $n < 6; $n++) {
                if (isset($activities[$n])) {
                    $activity = $activities[$n];
                    $hour = $hours[$n];
                    $note = $notes[$n];
                } else {
                    $activity = '<i>' . $noWorkday . '</i>';
                    $hour = '';
                    $note = '';
                }
                self::pdfWriteFixedPosHTML(
                    $mpdf,
                    $activity,
                    58,
                    73.0 + ($n - 1) * 17.8,
                    128,
                    15.8,
                    'auto',
                    'left',
                    false
                );
                self::pdfWriteFixedPosHTML($mpdf, $hour, 189, 73.0 + ($n - 1) * 17.8, 25, 15.8, 'auto', 'left', false);
                self::pdfWriteFixedPosHTML(
                    $mpdf,
                    $note,
                    217.5,
                    73.0 + ($n - 1) * 17.8,
                    46,
                    15.8,
                    'auto',
                    'justify'
                );
            }

            // añadir pie de firmas
            self::pdfWriteFixedPosHTML(
                $mpdf,
                (string)$agreementEnrollment->getStudentEnrollment()->getPerson(),
                68,
                185.4,
                53,
                5
            );
            self::pdfWriteFixedPosHTML(
                $mpdf,
                (string)$agreementEnrollment->getEducationalTutor(),
                136,
                186.9,
                53,
                5
            );
            self::pdfWriteFixedPosHTML(
                $mpdf,
                (string)$agreementEnrollment->getWorkTutor(),
                204,
                184.9,
                53,
                5
            );

            // si no está bloqueada la semana, agregar la marca de agua de borrador
            if (!$isLocked) {
                $mpdf->SetWatermarkText($translator->trans('form.draft', [], 'calendar'), 0.1);
                $mpdf->showWatermarkText = true;
                $mpdf->watermark_font = 'DejaVuSansCondensed';
            }

            $title = $translator->trans('title.weekly_activities', [], 'wpt_report')
                . ' - ' . $weekCounter['current'] . ' - ' . $agreementEnrollment->getStudentEnrollment() . ' - '
                . $agreementEnrollment->getAgreement()->getWorkcenter();

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

    public static function pdfWriteFixedPosHTML(
        Mpdf $mpdf,
        $text,
        $x,
        $y,
        $w,
        $h,
        $overflow = 'auto',
        string $align = 'left',
        $escape = true
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

    #[Route(path: '/api/v2/{id}', name: 'api_workplace_training_tracking_calendar_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function apiIndex(
        TrackedWorkDayRepository $trackedWorkDayRepository,
        ActivityRepository $activityRepository,
        ActivityTrackingRepository $activityTrackingRepository,
        AgreementEnrollment $agreementEnrollment
    ): Response
    {
        $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::ACCESS, $agreementEnrollment);

        $readOnly = !$this->isGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment);

        $workDays = $trackedWorkDayRepository
            ->findByAgreementEnrollmentGroupByMonthAndWeekNumber($agreementEnrollment);

        $newData = [];

        foreach ($workDays as $id => $month) {
            $newData[$id] = [];
            foreach ($month as $weekId => $week) {
                $newData[$id][$weekId] = [];
                foreach ($week['days'] as $dayId => $day) {
                    if (!isset($day[0])) {
                        $newData[$id][$weekId][$dayId] = [];
                    } elseif (!isset($day[1])) {
                        $newData[$id][$weekId][$dayId] = [
                            'id' => $day[0]->getId(),
                            'total_hours' => $day[0]->getHours() * 100,
                            'registered_hours' => 0,
                            'locked' => false,
                            'absence' => 0,
                            'start_time1' => null,
                            'end_time1' => null,
                            'start_time2' => null,
                            'end_time2' => null,
                            'other_activities' => null,
                            'notes' => null
                        ];
                    } else {
                        $newData[$id][$weekId][$dayId] = [
                            'id' => $day[0]->getId(),
                            'total_hours' => $day[0]->getHours() * 100,
                            'registered_hours' => $day[2] ?: 0,
                            'locked' => $day[1]->isLocked(),
                            'absence' => $day[1]->getAbsence(),
                            'start_time1' => $day[1]->getStartTime1(),
                            'end_time1' => $day[1]->getEndTime1(),
                            'start_time2' => $day[1]->getStartTime2(),
                            'end_time2' => $day[1]->getEndTime2(),
                            'other_activities' => $day[1]->getOtherActivities(),
                            'notes' => $day[1]->getNotes()
                        ];
                    }
                }
            }
        }

        $activityStats = $activityRepository
            ->getProgramActivitiesStatsFromAgreementEnrollmentQueryBuilder($agreementEnrollment)
            ->getQuery()->getArrayResult();

        $activityTotalCount = $activityTrackingRepository->getCountFromAgreementEnrollment($agreementEnrollment);
        $activityTrackedCount = $activityTrackingRepository
            ->getTrackedCountFromAgreementEnrollment($agreementEnrollment);
        $activityTotalHours = $activityTrackingRepository->getTotalHoursFromAgreementEnrollment($agreementEnrollment);

        $selectable = $this->isGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment) ||
            $this->isGranted(AgreementEnrollmentVoter::ATTENDANCE, $agreementEnrollment);

        return new JsonResponse([
                                    'selectable' => $selectable,
                                    'activity_stats' => $activityStats,
                                    'activity_tracked_count' => $activityTrackedCount,
                                    'activity_total_count' => $activityTotalCount,
                                    'activity_total_hours' => $activityTotalHours,
                                    'calendar' => $newData,
                                    'read_only' => $readOnly
                                ]);
    }

    #[Route(path: '/api/v2/jornada/{workDay}/{agreementEnrollment}', name: 'api_workplace_training_tracking_calendar_form', requirements: ['id' => '\d+', 'agreementEnrollment' => '\d+'], methods: ['GET'])]
    public function apiEdit(
        TrackedWorkDayRepository $trackedWorkDayRepository,
        ManagerRegistry $managerRegistry,
        WorkDay $workDay,
        AgreementEnrollment $agreementEnrollment
    ): Response
    {
        try {
            $trackedWorkDay = $trackedWorkDayRepository->findOneOrNewByWorkDayAndAgreementEnrollment(
                $workDay,
                $agreementEnrollment
            );

            $managerRegistry->getManager()->flush();
        } catch (\Exception) {
            return new JsonResponse([
                                        'result' => 'error'
                                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$trackedWorkDay instanceof TrackedWorkDay) {
            throw $this->createAccessDeniedException();
        }

        $this->denyAccessUnlessGranted(TrackedWorkDayVoter::ACCESS, $trackedWorkDay);
        $readOnly = !$this->isGranted(TrackedWorkDayVoter::FILL, $trackedWorkDay);

        $trackedActivities = $trackedWorkDay->getTrackedActivities();
        $activities = clone $agreementEnrollment->getActivities();

        foreach ($trackedActivities as $trackedActivity) {
            $activities->removeElement($trackedActivity->getActivity());
        }
        foreach ($activities as $newActivity) {
            $newTrackedActivity = new ActivityTracking();
            $newTrackedActivity
                ->setActivity($newActivity)
                ->setTrackedWorkDay($trackedWorkDay)
                ->setHours(0);
            $trackedActivities->add($newTrackedActivity);
        }

        $trackedActivitiesData = [];

        foreach ($trackedActivities as $trackedActivity) {
            $trackedActivitiesData[] = [
                'id' => $trackedActivity->getActivity()->getId(),
                'hours' => $trackedActivity->getHours(),
                'description' => $trackedActivity->getActivity()->getDescription(),
                'code' => $trackedActivity->getActivity()->getCode(),
                'notes' => $trackedActivity->getNotes()
            ];
        }
        return new JsonResponse([
                                    'read_only' => $readOnly,
                                    'tracked_work_day' => [
                                        'id' => $trackedWorkDay->getId(),
                                        'date' => $trackedWorkDay->getWorkDay()->getDate(),
                                        'hours' => $trackedWorkDay->getWorkDay()->getHours() * 100,
                                        'absence' => $trackedWorkDay->getAbsence(),
                                        'notes' => $trackedWorkDay->getNotes(),
                                        'start_time1' => $trackedWorkDay->getStartTime1(),
                                        'start_time2' => $trackedWorkDay->getStartTime2(),
                                        'end_time1' => $trackedWorkDay->getEndTime1(),
                                        'end_time2' => $trackedWorkDay->getEndTime2(),
                                        'other_activities' => $trackedWorkDay->getOtherActivities()
                                    ],
                                    'tracked_activities' => $trackedActivitiesData
                                ]);
    }

    #[Route(path: '/api/v2/jornada/modificar/{trackedWorkDay}', name: 'api_workplace_training_tracking_calendar_edit', requirements: ['trackedWorkDay' => '\d+'], methods: ['POST'])]
    public function apiEditPost(
        ManagerRegistry $managerRegistry,
        Request $request,
        TrackedWorkDay $trackedWorkDay
    ): Response
    {
        $this->denyAccessUnlessGranted(TrackedWorkDayVoter::FILL, $trackedWorkDay);

        if ($request->get('absence', null) !== null) {
            $this->denyAccessUnlessGranted(
                AgreementEnrollmentVoter::ATTENDANCE,
                $trackedWorkDay->getAgreementEnrollment()
            );
            $absence = (int) $request->get('absence');
            if ($absence !== TrackedWorkDay::NO_ABSENCE
                && $absence !== TrackedWorkDay::JUSTIFIED_ABSENCE
                && $absence !== TrackedWorkDay::UNJUSTIFIED_ABSENCE) {
                throw $this->createAccessDeniedException();
            }
            $trackedWorkDay->setAbsence($absence);
        }
        $trackedWorkDay->setNotes($request->get('notes'));
        $trackedWorkDay->setOtherActivities($request->get('other_activities'));
        $trackedWorkDay->setStartTime1($request->get('start_time1'));
        $trackedWorkDay->setStartTime2($request->get('start_time2'));
        $trackedWorkDay->setEndTime1($request->get('end_time1'));
        $trackedWorkDay->setEndTime2($request->get('end_time2'));

        $em = $managerRegistry->getManager();

        try {
            $em->flush();
            return new JsonResponse([
                                        'result' => 'ok'
                                    ]);
        } catch (\Exception) {
            return new JsonResponse([
                                        'result' => 'error'
                                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route(path: '/api/v2/jornada/actividad/{trackedWorkDay}/{activity}', name: 'api_workplace_training_tracking_calendar_edit_activity', requirements: ['trackedWorkDay' => '\d+', 'activity' => '\d+'], methods: ['POST'])]
    public function apiEditActivityPost(
        Request $request,
        ManagerRegistry $managerRegistry,
        TrackedWorkDay $trackedWorkDay,
        Activity $activity
    ): Response
    {
        $this->denyAccessUnlessGranted(TrackedWorkDayVoter::FILL, $trackedWorkDay);

        if ($request->get('hours', null) === null) {
            throw $this->createAccessDeniedException();
        }

        $trackedActivities = $trackedWorkDay->getTrackedActivities();
        $activities = clone $trackedWorkDay->getAgreementEnrollment()->getActivities();

        foreach ($trackedActivities as $trackedActivity) {
            $activities->removeElement($trackedActivity->getActivity());
        }
        foreach ($activities as $newActivity) {
            $newTrackedActivity = new ActivityTracking();
            $newTrackedActivity
                ->setActivity($newActivity)
                ->setTrackedWorkDay($trackedWorkDay)
                ->setHours(0);
            $trackedActivities->add($newTrackedActivity);
        }

        $em = $managerRegistry->getManager();

        $found = false;
        foreach ($trackedActivities as $trackedActivity) {
            if ($trackedActivity->getActivity() === $activity) {
                $trackedActivity->setHours((int)$request->get('hours'));
                $found = true;
            }
            if ($trackedActivity->getHours() == 0) {
                $trackedActivities->removeElement($trackedActivity);
                if ($trackedWorkDay->getId() !== null) {
                    $managerRegistry->getManager()->remove($trackedActivity);
                }
            } else {
                $managerRegistry->getManager()->persist($trackedActivity);
            }
        }

        if (!$found) {
            return new JsonResponse([
                                        'result' => 'not found'
                                    ], Response::HTTP_NOT_FOUND);
        }

        try {
            $oldTrackedActivities = $trackedWorkDay->getTrackedActivities();
            foreach ($oldTrackedActivities as $oldTrackedActivity) {
                if (!$trackedActivities->contains($oldTrackedActivity)) {
                    $em->remove($oldTrackedActivity);
                }
            }
            foreach ($trackedActivities as $trackedActivity) {
                $trackedActivity->setTrackedWorkDay($trackedWorkDay);
            }

            $em->flush();
            return new JsonResponse([
                                        'result' => 'ok'
                                    ]);
        } catch (\Exception) {
            return new JsonResponse([
                                        'result' => 'error'
                                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
