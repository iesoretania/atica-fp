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

namespace AppBundle\Controller\WLT;

use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\WorkDay;
use AppBundle\Form\Type\WLT\WorkDayTrackingType;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Repository\WLT\AgreementActivityRealizationRepository;
use AppBundle\Repository\WLT\AgreementRepository;
use AppBundle\Repository\WLT\WorkDayRepository;
use AppBundle\Security\WLT\AgreementVoter;
use AppBundle\Security\WLT\WorkDayVoter;
use Mpdf\Mpdf;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

/**
 * @Route("/dual/seguimiento/calendario")
 */
class TrackingCalendarController extends Controller
{
    /**
     * @Route("/{id}", name="work_linked_training_tracking_calendar_list",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function indexAction(
        WorkDayRepository $workDayRepository,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository,
        TranslatorInterface $translator,
        Agreement $agreement
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);

        $readOnly = !$this->isGranted(AgreementVoter::MANAGE, $agreement);

        $workDaysData = $workDayRepository->findByAgreementGroupByMonthAndWeekNumber($agreement);

        $today = new \DateTime('', new \DateTimeZone('UTC'));
        $today->setTime(0, 0);
        $workDayToday = $workDayRepository->findOneByAgreementAndDate($agreement, $today);

        $workDayStats = count($agreement->getWorkDays()) > 0
            ? $workDayRepository->hoursStatsByAgreement($agreement)
            : [];

        $activityRealizations = $agreementActivityRealizationRepository->findByAgreementSorted($agreement);

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
            'work_day_stats' => $workDayStats,
            'work_day_today' => $workDayToday,
            'calendar' => $workDaysData,
            'read_only' => $readOnly,
            'back_url' => $backUrl
        ]);
    }

    /**
     * @Route("/jornada/{id}", name="work_linked_training_tracking_calendar_form",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        AgreementRepository $agreementRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        TranslatorInterface $translator,
        WorkDay $workDay
    ) {
        $agreement = $workDay->getAgreement();
        $this->denyAccessUnlessGranted(WorkDayVoter::ACCESS, $workDay);
        $readOnly = !$this->isGranted(WorkDayVoter::FILL, $workDay);

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));
        $title .= ' - ' . $translator->transChoice('caption.hours', $workDay->getHours(), [], 'calendar');

        $lockedActivityRealizations = $activityRealizationRepository->findLockedByAgreement($agreement);

        // precaching
        $activityRealizationRepository->findByAgreement($agreement);

        $oldActivityRealizations = clone $workDay->getActivityRealizations();

        $form = $this->createForm(WorkDayTrackingType::class, $workDay, [
            'work_day' => $workDay,
            'locked_activity_realizations' => $lockedActivityRealizations
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
                    if (!$lockManager && count($invalid) > 0) {
                        throw $this->createAccessDeniedException();
                    }

                    // asegurar que no se pierden las concreciones marcadas pero bloqueadas
                    $toInsert = array_intersect($lockedActivityRealizations, $oldActivityRealizations->toArray());
                    foreach ($toInsert as $activityRealization) {
                        if ($workDay->getActivityRealizations()->contains($activityRealization) === false) {
                            $workDay->getActivityRealizations()->add($activityRealization);
                        }
                    }
                } else {
                    $workDay->getActivityRealizations()->clear();
                }
                $this->getDoctrine()->getManager()->flush();

                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.workday_saved', [], 'calendar'));
                return $this->redirectToRoute('work_linked_training_tracking_calendar_list', [
                    'id' => $agreement->getId()
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
            'title' => $title
        ]);
    }

    /**
     * @Route("/{id}/operacion", name="work_linked_training_tracking_calendar_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        Agreement $agreement
    ) {

        if ($request->get('lock_week')) {
            $year = floor($request->get('lock_week') / 100);
            $week = $request->get('lock_week') % 100;
            $workDayRepository->updateWeekLock($year, $week, $agreement, true);
        } else if ($request->get('unlock_week')) {
            $year = floor($request->get('unlock_week') / 100);
            $week = $request->get('unlock_week') % 100;
            $workDayRepository->updateWeekLock($year, $week, $agreement, false);
        } else if ($request->get('week_report')) {
            return $this->redirectToRoute(
                'work_linked_training_tracking_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
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
                $this->getDoctrine()->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.locked', [], 'calendar'));
            } catch (\Exception $e) {
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
                $this->getDoctrine()->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.attendance_updated', [], 'calendar'));
            } catch (\Exception $e) {
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
                'fixed' => (string) $agreement,
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
     * @Route("/{id}/asistencia", name="work_linked_training_tracking_calendar_attendance_report", methods={"GET"})
     * @Security("is_granted('WLT_AGREEMENT_ACCESS', agreement)")
     */
    public function attendanceReportAction(
        Environment $engine,
        TranslatorInterface $translator,
        Agreement $agreement
    ) {
        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            if ($agreement->getProject()->getAttendanceReportTemplate()) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $agreement->getProject()->getAttendanceReportTemplate()->getData());
                $mpdf->SetImportUse();
                $mpdf->SetDocTemplate($tmp);
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
}
