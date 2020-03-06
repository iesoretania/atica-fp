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

namespace AppBundle\Controller\WPT;

use AppBundle\Entity\WPT\ActivityTracking;
use AppBundle\Entity\WPT\AgreementEnrollment;
use AppBundle\Entity\WPT\TrackedWorkDay;
use AppBundle\Entity\WPT\WorkDay;
use AppBundle\Form\Type\WPT\WorkDayTrackingType;
use AppBundle\Repository\WPT\AgreementRepository;
use AppBundle\Repository\WPT\TrackedWorkDayRepository;
use AppBundle\Repository\WPT\WorkDayRepository;
use AppBundle\Security\WPT\AgreementEnrollmentVoter;
use AppBundle\Security\WPT\AgreementVoter;
use AppBundle\Security\WPT\WorkDayVoter;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

/**
 * @Route("/fct/seguimiento/calendario")
 */
class TrackingCalendarController extends Controller
{
    /**
     * @Route("/{id}", name="workplace_training_tracking_calendar_list",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function indexAction(
        TrackedWorkDayRepository $trackedWorkDayRepository,
        WorkDayRepository $workDayRepository,
        TranslatorInterface $translator,
        AgreementEnrollment $agreementEnrollment
    ) {
        $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::ACCESS, $agreementEnrollment);

        $readOnly = !$this->isGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment);

        $workDaysData = $trackedWorkDayRepository
            ->findByAgreementEnrollmentGroupByMonthAndWeekNumber($agreementEnrollment);

        $agreement = $agreementEnrollment->getAgreement();

        $today = new \DateTime('', new \DateTimeZone('UTC'));
        $today->setTime(0, 0);
        $workDayToday = $workDayRepository->findOneByAgreementAndDate($agreement, $today);

        $workDayStats = count($agreement->getWorkDays()) > 0
            ? $trackedWorkDayRepository->hoursStatsByAgreementEnrollment($agreementEnrollment)
            : [];

        $title = $translator->trans('title.calendar', [], 'wpt_tracking');

        $breadcrumb = [
            ['fixed' => (string) $agreementEnrollment],
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
            'calendar' => $workDaysData,
            'read_only' => $readOnly,
            'back_url' => $backUrl
        ]);
    }

    /**
     * @Route("/jornada/{workDay}/{agreementEnrollment}", name="workplace_training_tracking_calendar_form",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        WorkDay $workDay,
        AgreementEnrollment $agreementEnrollment
    ) {
        $trackedWorkDay = $trackedWorkDayRepository->findOneOrNewByWorkDayAndAgreementEnrollment(
            $workDay,
            $agreementEnrollment
        );

        if (null === $trackedWorkDay) {
            throw $this->createAccessDeniedException();
        }

        $agreement = $workDay->getAgreement();
        $this->denyAccessUnlessGranted(WorkDayVoter::ACCESS, $workDay);
        $readOnly = !$this->isGranted(WorkDayVoter::FILL, $workDay);

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));
        $title .= ' - ' . $translator->transChoice('caption.hours', $workDay->getHours(), [], 'calendar');

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
        $trackedWorkDay->setTrackedActivities($trackedActivities);

        $form = $this->createForm(WorkDayTrackingType::class, $trackedWorkDay, [
            'work_day' => $trackedWorkDay
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
                            if ($trackedWorkDay->getId()) {
                                $this->getDoctrine()->getManager()->remove($trackedActivity);
                            }
                        } else {
                            $this->getDoctrine()->getManager()->persist($trackedActivity);
                        }
                    }
                }
                $trackedWorkDay->setTrackedActivities($trackedActivities);
                $this->getDoctrine()->getManager()->flush();

                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.workday_saved', [], 'calendar'));
                return $this->redirectToRoute('workplace_training_tracking_calendar_list', [
                    'id' => $agreementEnrollment->getId()
                ]);
            } catch (AccessDeniedException $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.workday_save_error', [], 'calendar'));
            }
        }

        $breadcrumb = [
            [
                'fixed' => (string) $agreement,
                'routeName' => 'workplace_training_tracking_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/tracking/calendar_form.html.twig', [
            'menu_path' => 'workplace_training_tracking_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'read_only' => $readOnly,
            'tracked_work_day' => $trackedWorkDay,
            'title' => $title
        ]);
    }

    /**
     * @Route("/{id}/operacion", name="workplace_training_tracking_calendar_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        TranslatorInterface $translator,
        AgreementEnrollment $agreementEnrollment
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreementEnrollment->getAgreement());
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

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_list',
                ['id' => $agreementEnrollment->getId()]
            );
        }

        $trackedWorkDays = $trackedWorkDayRepository->findInListByWorkDayIdAndAgreementEnrollment($items, $agreementEnrollment);

        // comprobar si es bloqueo de jornadas
        $locked = $request->get('lock') === '';
        if ($locked || $request->get('unlock') === '') {
            $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::LOCK, $agreementEnrollment);
            try {
                $trackedWorkDayRepository->updateLock($trackedWorkDays, $agreementEnrollment, $locked);
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.locked', [], 'calendar'));
            } catch (\Exception $e) {
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
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.attendance_updated', [], 'calendar'));
            } catch (\Exception $e) {
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
                'fixed' => (string) $agreementEnrollment,
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

    /**
     * @Route("/{id}/asistencia/descargar",
     *     name="workplace_training_tracking_calendar_attendance_report", methods={"GET"})
     * @Security("is_granted('WPT_AGREEMENT_ENROLLMENT_ACCESS', agreementEnrollment)")
     */
    public function attendanceReportAction(
        Environment $engine,
        TranslatorInterface $translator,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        AgreementEnrollment $agreementEnrollment
    ) {
        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            $template = $agreementEnrollment->getAgreement()->getShift()->getAttendanceReportTemplate();
            if ($template) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetImportUse();
                $mpdf->SetDocTemplate($tmp);
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

    /**
     * @Route("/{id}/informe/{year}/{week}", name="workplace_training_tracking_calendar_activity_report",
     *     methods={"GET"})
     * @Security("is_granted('WPT_AGREEMENT_ENROLLMENT_ACCESS', agreementEnrollment)")
     */
    public function activityReportAction(
        TranslatorInterface $translator,
        AgreementEnrollment $agreementEnrollment,
        TrackedWorkDayRepository $trackedWorkDayRepository,
        $year,
        $week
    ) {
        $weekDays = $trackedWorkDayRepository->findByYearWeekAndAgreementEnrollment($year, $week, $agreementEnrollment);

        if (count($weekDays) === 0) {
            // no hay jornadas, volver al listado
            return $this->redirectToRoute(
                'workplace_training_tracking_calendar_list',
                ['id' => $agreementEnrollment->getId()]
            );
        }

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            if ($agreementEnrollment->getAgreement()->getShift()->getWeeklyActivityReportTemplate()) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents(
                    $tmp,
                    $agreementEnrollment->getAgreement()->getShift()->getWeeklyActivityReportTemplate()->getData()
                );
                $mpdf->SetImportUse();
                $mpdf->SetDocTemplate($tmp);
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
                if (false === $trackedWorkDay->isLocked()) {
                    $isLocked = false;
                }
                $day = $trackedWorkDay->getWorkDay()->getDate()->format('N');
                $activities[$day] = '';
                $hours[$day] = '';

                foreach ($trackedWorkDay->getTrackedActivities() as $trackedActivity) {
                    if ($trackedActivity->getActivity()->getCode()) {
                        $activities[$day] .= '<b>' .
                            htmlentities($trackedActivity->getActivity()->getCode()) . ': </b>';
                    }
                    $activities[$day] .= htmlentities($trackedActivity->getActivity()->getDescription()) . '<br/>';
                    if ($hours[$day] === '') {
                        $hours[$day] =
                            '<ul style="list-style-position: inside; padding: 0; margin: 0; list-style: square;">';
                    }
                    $hours[$day] .= '<li>' . $translator->transChoice(
                        'form.r_hours',
                        $trackedActivity->getHours(),
                        ['%hours%' => $trackedActivity->getHours() / 100.0],
                        'calendar'
                    );
                }
                if ($hours[$day] !== '') {
                    $hours[$day] .= '</ul>';
                }

                if ($trackedWorkDay->getOtherActivities()) {
                    $activities[$day] .= htmlentities($trackedWorkDay->getOtherActivities()) . '<br/>';
                }

                if ('' === $activities[$day]) {
                    $activities[$day] = '<i>' . $noActivity . '</i>';
                }
                $notes[$day] = $trackedWorkDay->getNotes();
            }

            $mpdf->AddPage('L');

            // añadir fecha a la ficha
            $first = end($weekDays);
            $last = reset($weekDays);

            self::pdfWriteFixedPosHTML($mpdf, $first->getWorkDay()->getDate()->format('j'), 54.5, 33.5, 8, 5, 'auto', 'center');
            self::pdfWriteFixedPosHTML($mpdf, $last->getWorkDay()->getDate()->format('j'), 67.5, 33.5, 10, 5, 'auto', 'center');
            self::pdfWriteFixedPosHTML($mpdf, $translator->trans('r_month' . ($last->getWorkDay()->getDate()->format('n') - 1), [], 'calendar'), 85, 33.5, 23.6, 5, 'auto', 'center');
            self::pdfWriteFixedPosHTML($mpdf, $last->getWorkDay()->getDate()->format('y'), 118.5, 33.5, 6, 5, 'auto', 'center');

            // añadir números de página
            $weekCounter = $trackedWorkDayRepository->getWeekInformation($first);
            self::pdfWriteFixedPosHTML($mpdf, $weekCounter['current'], 245.5, 21.9, 6, 5, 'auto', 'center');
            self::pdfWriteFixedPosHTML($mpdf, $weekCounter['total'], 254.8, 21.9, 6, 5, 'auto', 'center');

            // añadir campos de la cabecera
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getAgreement()->getWorkcenter(), 192, 40.8, 72, 5, 'auto', 'left');
            self::pdfWriteFixedPosHTML($mpdf, $agreementEnrollment->getAgreement()->getShift()->getGrade()->getTraining()->getAcademicYear()->getOrganization(), 62.7, 40.9, 80, 5, 'auto', 'left');
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getEducationalTutor(), 97.5, 46.5, 46, 5, 'auto', 'left');
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getWorkTutor(), 198, 46.5, 66, 5, 'auto', 'left');
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getStudentEnrollment()->getGroup()->getGrade()->getTraining(), 172, 54, 61, 5, 'auto', 'left');
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getAgreement()->getShift()->getType(), 244, 54, 20, 5, 'auto', 'left');
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getStudentEnrollment()->getPerson(), 63, 54, 80, 5, 'auto', 'left');

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
                self::pdfWriteFixedPosHTML($mpdf, $activity, 58, 73.0 + ($n - 1) * 17.8, 128, 15.8, 'auto', 'left', false);
                self::pdfWriteFixedPosHTML($mpdf, $hour, 189, 73.0 + ($n - 1) * 17.8, 25, 15.8, 'auto', 'left', false);
                self::pdfWriteFixedPosHTML($mpdf, $note, 217.5, 73.0 + ($n - 1) * 17.8, 46, 15.8, 'auto', 'justify', true);
            }

            // añadir pie de firmas
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getStudentEnrollment()->getPerson(), 68, 185.4, 53, 5, 'auto', 'left');
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getEducationalTutor(), 136, 186.9, 53, 5, 'auto', 'left');
            self::pdfWriteFixedPosHTML($mpdf, (string) $agreementEnrollment->getWorkTutor(), 204, 184.9, 53, 5, 'auto', 'left');

            // si no está bloqueada la semana, agregar la marca de agua de borrador
            if (!$isLocked) {
                $mpdf->SetWatermarkText($translator->trans('form.draft', [], 'calendar'), 0.1);
                $mpdf->showWatermarkText = true;
                $mpdf->watermark_font = 'DejaVuSansCondensed';
            }

            $title = $translator->trans('title.weekly_activities', [], 'wlt_report')
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
        $align = 'left',
        $escape = true
    ) {
        if ($escape) {
            $text = nl2br(htmlentities($text));
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