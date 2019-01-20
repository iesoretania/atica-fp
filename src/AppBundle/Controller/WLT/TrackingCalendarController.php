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

use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\WorkDay;
use AppBundle\Form\Type\WLT\WorkDayTrackingType;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Repository\WLT\AgreementActivityRealizationRepository;
use AppBundle\Repository\WLT\AgreementRepository;
use AppBundle\Repository\WLT\WorkDayRepository;
use AppBundle\Security\WLT\AgreementVoter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Translation\TranslatorInterface;

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

        $workDaysData = $workDayRepository->findByAgreementGroupByMonthAndWeekNumber($agreement);
        $today = new \DateTime('', new \DateTimeZone('UTC'));
        $today->setTime(0, 0);
        $workDayToday = $workDayRepository->findOneByAgreementAndDate($agreement, $today);

        $workDayStats = $workDayRepository->hoursStatsByAgreement($agreement);
        $activityRealizations = $agreementActivityRealizationRepository->findByAgreementSorted($agreement);

        $title = $translator->trans('title.calendar', [], 'wlt_tracking');

        $breadcrumb = [
            ['fixed' => (string) $agreement],
            ['fixed' => $title]
        ];

        $selectable = $this->isGranted(AgreementVoter::LOCK, $agreement) ||
            $this->isGranted(AgreementVoter::ATTENDANCE, $agreement);

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
            'calendar' => $workDaysData
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
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));
        $title .= ' - ' . $translator->transChoice('caption.hours', $workDay->getHours(), [], 'calendar');

        $lockedActivityRealizations = $activityRealizationRepository->findLockedByAgreement($agreement);
        $oldActivityRealizations = clone $workDay->getActivityRealizations();

        $form = $this->createForm(WorkDayTrackingType::class, $workDay, [
            'work_day' => $workDay,
            'locked_activity_realizations' => $lockedActivityRealizations
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $currentActivityRealizations = $workDay->getActivityRealizations();
                $toInsert = array_diff($currentActivityRealizations->toArray(), $oldActivityRealizations->toArray());

                // comprobar que no se intenta activar una concreción ya bloqueada
                $invalid = array_intersect($toInsert, $lockedActivityRealizations);
                if (count($invalid) > 0) {
                    throw $this->createAccessDeniedException();
                }

                // asegurar que no se pierden las concreciones marcadas pero bloqueadas
                $toInsert = array_intersect($lockedActivityRealizations, $oldActivityRealizations->toArray());
                foreach ($toInsert as $activityRealization) {
                    if ($workDay->getActivityRealizations()->contains($activityRealization) === false) {
                        $workDay->getActivityRealizations()->add($activityRealization);
                    }
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
                $workDayRepository->updateLock($workDays, $locked);
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
}
