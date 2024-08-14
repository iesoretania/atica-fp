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

namespace App\Controller\WLT;

use App\Entity\Edu\ReportTemplate;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\WorkDay;
use App\Form\Type\WLT\WorkDayTrackingType;
use App\Repository\WLT\ActivityRealizationRepository;
use App\Repository\WLT\AgreementActivityRealizationRepository;
use App\Repository\WLT\AgreementRepository;
use App\Repository\WLT\WorkDayRepository;
use App\Security\WLT\AgreementVoter;
use App\Security\WLT\WorkDayVoter;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

#[Route(path: '/dual/seguimiento/calendario')]
class TrackingCalendarController extends AbstractController
{
    #[Route(path: '/{id}', name: 'work_linked_training_tracking_calendar_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(
        WorkDayRepository $workDayRepository,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository,
        TranslatorInterface $translator,
        Agreement $agreement
    ): Response
    {
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);

        $readOnly = !$this->isGranted(AgreementVoter::LOCK, $agreement);

        $workDaysData = $workDayRepository->findByAgreementGroupByMonthAndWeekNumber($agreement);

        $today = new \DateTime('', new \DateTimeZone('UTC'));
        $today->setTime(0, 0);
        $workDayToday = $workDayRepository->findOneByAgreementAndDate($agreement, $today);

        $workDayStats = $agreement->getWorkDays() !== []
            ? $workDayRepository->hoursStatsByAgreement($agreement)
            : [];

        $activityRealizations = $agreementActivityRealizationRepository->findByAgreementSorted($agreement);
        $workedActivityRealizations = $agreementActivityRealizationRepository->findSubmittedByAgreement($agreement);

        $title = $translator->trans('title.calendar', [], 'wlt_tracking');

        $breadcrumb = [
            ['fixed' => (string) $agreement],
            ['fixed' => $title]
        ];

        $selectable = $this->isGranted(AgreementVoter::LOCK, $agreement) ||
            $this->isGranted(AgreementVoter::ATTENDANCE, $agreement);

        $backUrl = $this->generateUrl('work_linked_training_tracking_list', [
            'academicYear' => $agreement
                ->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear()->getId()
        ]);

        return $this->render('wlt/tracking/calendar.html.twig', [
            'menu_path' => 'work_linked_training_tracking_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'show_title' => false,
            'agreement' => $agreement,
            'selectable' => $selectable,
            'activity_realizations' => $activityRealizations,
            'worked_activity_realizations' => $workedActivityRealizations,
            'work_day_stats' => $workDayStats,
            'work_day_today' => $workDayToday,
            'calendar' => $workDaysData,
            'read_only' => $readOnly,
            'back_url' => $backUrl
        ]);
    }

    #[Route(path: '/jornada/{id}', name: 'work_linked_training_tracking_calendar_form', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        AgreementRepository $agreementRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        WorkDay $workDay,
        WorkDayRepository $workDayRepository
    ): Response
    {
        $agreement = $workDay->getAgreement();
        $this->denyAccessUnlessGranted(WorkDayVoter::ACCESS, $workDay);
        $readOnly = !$this->isGranted(WorkDayVoter::FILL, $workDay);

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));
        $title .= ' - ' . $translator->trans('caption.hours', ['count' => $workDay->getHours()], 'calendar');

        $lockedActivityRealizations = $activityRealizationRepository->findLockedByAgreement($agreement);

        $previousWorkDay = $workDayRepository->findPrevious($workDay);
        $nextWorkDay = $workDayRepository->findNext($workDay);

        // precaching
        $activityRealizationRepository->findByAgreement($agreement);

        $oldActivityRealizations = clone $workDay->getActivityRealizations();

        $form = $this->createForm(WorkDayTrackingType::class, $workDay, [
            'work_day' => $workDay,
            'locked_activity_realizations' => $lockedActivityRealizations,
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($workDay->getAbsence() === WorkDay::NO_ABSENCE) {
                    $lockManager = $this->isGranted(AgreementVoter::LOCK, $agreement);
                    $currentActivityRealizations = $workDay->getActivityRealizations();
                    $toInsert = array_diff(
                        $currentActivityRealizations->toArray(),
                        $oldActivityRealizations->toArray()
                    );

                    // comprobar que no se intenta activar una concreción ya bloqueada
                    $invalid = array_intersect($toInsert, $lockedActivityRealizations);
                    if (!$lockManager && $invalid !== []) {
                        throw $this->createAccessDeniedException();
                    }

                    // asegurar que no se pierden las concreciones marcadas pero bloqueadas
                    $toInsert = array_intersect($lockedActivityRealizations, $oldActivityRealizations->toArray());
                    foreach ($toInsert as $activityRealization) {
                        if (!$workDay->getActivityRealizations()->contains($activityRealization)) {
                            $workDay->getActivityRealizations()->add($activityRealization);
                        }
                    }
                } else {
                    $workDay->getActivityRealizations()->clear();
                }
                $managerRegistry->getManager()->flush();

                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.workday_saved', [], 'calendar'));
                return $this->redirectToRoute('work_linked_training_tracking_calendar_list', [
                    'id' => $agreement->getId()
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
                'routeName' => 'work_linked_training_tracking_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/tracking/calendar_form.html.twig', [
            'menu_path' => 'work_linked_training_tracking_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'read_only' => $readOnly,
            'work_day' => $workDay,
            'previous_work_day' => $previousWorkDay,
            'next_work_day' => $nextWorkDay,
            'title' => $title
        ]);
    }

    #[Route(path: '/{id}/operacion', name: 'work_linked_training_tracking_calendar_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Agreement $agreement
    ): Response
    {
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);
        if ($request->get('week_report')) {
            $year = floor($request->get('week_report') / 100);
            $week = $request->get('week_report') % 100;
            return $this->redirectToRoute(
                'work_linked_training_tracking_calendar_activity_report',
                ['id' => $agreement->getId(), 'year' => $year, 'week' => $week]
            );
        }

        $this->denyAccessUnlessGranted(AgreementVoter::LOCK, $agreement);

        if ($request->get('lock_week')) {
            $year = floor($request->get('lock_week') / 100);
            $week = $request->get('lock_week') % 100;
            $workDayRepository->updateWeekLock($year, $week, $agreement, true);
            return $this->redirectToRoute(
                'work_linked_training_tracking_calendar_list',
                ['id' => $agreement->getId()]
            );
        } elseif ($request->get('unlock_week')) {
            $year = floor($request->get('unlock_week') / 100);
            $week = $request->get('unlock_week') % 100;
            $workDayRepository->updateWeekLock($year, $week, $agreement, false);
            return $this->redirectToRoute(
                'work_linked_training_tracking_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute(
                'work_linked_training_tracking_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        $workDays = $workDayRepository->findInListByIdAndAgreement($items, $agreement);

        // comprobar si es bloqueo de jornadas
        $locked = $request->get('lock') === '';
        if ($locked || $request->get('unlock') === '') {
            $this->denyAccessUnlessGranted(AgreementVoter::LOCK, $agreement);
            try {
                $workDayRepository->updateLock($workDays, $agreement, $locked);
                $managerRegistry->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.locked', [], 'calendar'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.locked_error', [], 'calendar'));
            }
            return $this->redirectToRoute(
                'work_linked_training_tracking_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        // marcar en las jornadas que estudiante no ha estado en el centro de trabajo
        $this->denyAccessUnlessGranted(AgreementVoter::ATTENDANCE, $agreement);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $workDayRepository->updateAttendance($workDays, true);
                $managerRegistry->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.attendance_updated', [], 'calendar'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.attendance_error', [], 'calendar'));
            }
            return $this->redirectToRoute(
                'work_linked_training_tracking_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        $title = $translator->trans('title.attendance', [], 'calendar');

        $breadcrumb = [
            [
                'fixed' => (string)$agreement,
                'routeName' => 'work_linked_training_tracking_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar_attendance.html.twig', [
            'menu_path' => 'work_linked_training_tracking_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'items' => $workDays
        ]);
    }

    /**
     * @Security("is_granted('WLT_AGREEMENT_ACCESS', agreement)")
     */
    #[Route(path: '/{id}/asistencia', name: 'work_linked_training_tracking_calendar_attendance_report', methods: ['GET'])]
    public function attendanceReport(
        Environment $engine,
        TranslatorInterface $translator,
        Agreement $agreement
    ) {
        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);
        ini_set("pcre.backtrack_limit", "5000000");

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            if ($agreement->getProject()->getAttendanceReportTemplate() instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $agreement->getProject()->getAttendanceReportTemplate()->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }

            $title = $translator->trans('title.attendance', [], 'wlt_report')
                . ' - ' . $agreement->getStudentEnrollment() . ' - '
                . $agreement->getWorkcenter();

            $fileName = $title . '.pdf';

            $html = $engine->render('wlt/tracking/attendance_report.html.twig', [
                'agreement' => $agreement,
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

    /**
     * @Security("is_granted('WLT_AGREEMENT_ACCESS', agreement)")
     */
    #[Route(path: '/{id}/informe/descargar/{year}/{week}', name: 'work_linked_training_tracking_calendar_activity_report', methods: ['GET'])]
    public function activityReport(
        TranslatorInterface $translator,
        Agreement $agreement,
        WorkDayRepository $workDayRepository,
        $year,
        $week
    ): Response
    {
        $weekDays = $workDayRepository->findByYearWeekAndAgreement($year, $week, $agreement);

        if ((is_countable($weekDays) ? count($weekDays) : 0) === 0) {
            // no hay jornadas, volver al listado
            return $this->redirectToRoute('work_linked_training_tracking_calendar_list', ['id' => $agreement->getId()]);
        }

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);
        ini_set("pcre.backtrack_limit", "5000000");

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            if ($agreement->getProject()->getWeeklyActivityReportTemplate() instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $agreement->getProject()->getWeeklyActivityReportTemplate()->getData());
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

            /** @var WorkDay $workDay */
            foreach ($weekDays as $workDay) {
                if (!$workDay->isLocked()) {
                    $isLocked = false;
                }
                $day = $workDay->getDate()->format('N');
                $activities[$day] = '';
                $hours[$day] = $translator->trans(
                    'form.r_hours',
                    ['count' => $workDay->getHours()],
                    'calendar'
                );

                foreach ($workDay->getActivityRealizations() as $activityRealization) {
                    if ($activityRealization->getCode() !== '' && $activityRealization->getCode() !== null) {
                        $activities[$day] .= '<b>' . htmlentities((string) $activityRealization->getCode()) . ': </b>';
                    }
                    $activities[$day] .= htmlentities((string) $activityRealization->getDescription()) . '<br/>';
                }

                if ($workDay->getOtherActivities() !== '' && $workDay->getOtherActivities() !== null) {
                    $activities[$day] .= htmlentities((string) $workDay->getOtherActivities()) . '<br/>';
                }

                if ('' === $activities[$day]) {
                    $activities[$day] = '<i>' . $noActivity . '</i>';
                }
                $notes[$day] = $workDay->getNotes();
            }

            $mpdf->AddPage('L');

            // añadir fecha a la ficha
            $first = reset($weekDays);
            $last = end($weekDays);

            $this->pdfWriteFixedPosHTML($mpdf, $first->getDate()->format('j'), 54.5, 33.5, 8, 5, 'auto', 'center');
            $this->pdfWriteFixedPosHTML($mpdf, $last->getDate()->format('j'), 67.5, 33.5, 10, 5, 'auto', 'center');
            $this->pdfWriteFixedPosHTML(
                $mpdf,
                $translator->trans(
                    'r_month' . ($last->getDate()->format('n') - 1),
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
            $this->pdfWriteFixedPosHTML($mpdf, $last->getDate()->format('y'), 118.5, 33.5, 6, 5, 'auto', 'center');

            // añadir números de página
            $weekCounter = $workDayRepository->getWeekInformation($first);
            $this->pdfWriteFixedPosHTML($mpdf, $weekCounter['current'], 245.5, 21.9, 6, 5, 'auto', 'center');
            $this->pdfWriteFixedPosHTML($mpdf, $weekCounter['total'], 254.8, 21.9, 6, 5, 'auto', 'center');

            // añadir campos de la cabecera
            $this->pdfWriteFixedPosHTML($mpdf, (string)$agreement->getWorkcenter(), 192, 40.8, 72, 5);
            $this->pdfWriteFixedPosHTML($mpdf, $agreement->getProject()->getOrganization(), 62.7, 40.9, 80, 5);
            $this->pdfWriteFixedPosHTML($mpdf, (string)$agreement->getEducationalTutor(), 97.5, 46.5, 46, 5);
            $this->pdfWriteFixedPosHTML($mpdf, (string)$agreement->getWorkTutor(), 198, 46.5, 66, 5);
            $this->pdfWriteFixedPosHTML(
                $mpdf,
                (string)$agreement->getStudentEnrollment()->getGroup()->getGrade()->getTraining(),
                172,
                54,
                61,
                5
            );
            $this->pdfWriteFixedPosHTML($mpdf, (string)$agreement->getStudentEnrollment()->getPerson(), 63, 54, 80, 5);

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
                $this->pdfWriteFixedPosHTML(
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
                $this->pdfWriteFixedPosHTML($mpdf, $hour, 189, 73.0 + ($n - 1) * 17.8, 25, 15.8, 'auto', 'left', false);
                $this->pdfWriteFixedPosHTML($mpdf, $note, 217.5, 73.0 + ($n - 1) * 17.8, 46, 15.8, 'auto', 'justify');
            }

            // añadir pie de firmas
            $this->pdfWriteFixedPosHTML(
                $mpdf,
                (string)$agreement->getStudentEnrollment()->getPerson(),
                68,
                185.4,
                53,
                5
            );
            $this->pdfWriteFixedPosHTML($mpdf, (string)$agreement->getEducationalTutor(), 136, 186.9, 53, 5);
            $this->pdfWriteFixedPosHTML($mpdf, (string)$agreement->getWorkTutor(), 204, 184.9, 53, 5);

            // si no está bloqueada la semana, agregar la marca de agua de borrador
            if (!$isLocked) {
                $mpdf->SetWatermarkText($translator->trans('form.draft', [], 'calendar'), 0.1);
                $mpdf->showWatermarkText = true;
                $mpdf->watermark_font = 'DejaVuSansCondensed';
            }

            $title = $translator->trans('title.weekly_activities', [], 'wlt_report')
                . ' - ' . $weekCounter['current'] . ' - ' . $agreement->getStudentEnrollment() . ' - '
                . $agreement->getWorkcenter();

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
        $text,
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
