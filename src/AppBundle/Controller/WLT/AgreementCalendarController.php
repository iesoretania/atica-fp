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
use AppBundle\Form\Model\CalendarAdd;
use AppBundle\Form\Type\WLT\CalendarAddType;
use AppBundle\Form\Type\WLT\WorkDayType;
use AppBundle\Repository\WLT\AgreementRepository;
use AppBundle\Repository\WLT\WorkDayRepository;
use AppBundle\Security\WLT\AgreementVoter;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/acuerdo/calendario")
 */
class AgreementCalendarController extends Controller
{
    /**
     * @Route("/{id}", name="work_linked_training_agreement_calendar_list",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function indexAction(
        WorkDayRepository $workDayRepository,
        TranslatorInterface $translator,
        Agreement $agreement
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);

        $workDaysData = $workDayRepository->findByAgreementGroupByMonthAndWeekNumber($agreement);

        $title = $translator->trans('title.calendar', [], 'wlt_agreement');

        $breadcrumb = [
            ['fixed' => (string) $agreement],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar.html.twig', [
            'menu_path' => 'work_linked_training_agreement_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'calendar' => $workDaysData
        ]);
    }

    /**
     * @Route("/{id}/incorporar", name="work_linked_training_agreement_calendar_add",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function addAction(
        Request $request,
        TranslatorInterface $translator,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        Agreement $agreement
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);

        $title = $translator->trans('title.calendar.add', [], 'wlt_agreement');

        $calendarAdd = new CalendarAdd();
        $form = $this->createForm(CalendarAddType::class, $calendarAdd);

        $form->handleRequest($request);

        $workDays = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $workDays = $workDayRepository->createWorkDayCollectionByAcademicYearGroupByMonthAndWeekNumber(
                $agreement,
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
                $calendarAdd->getOverwriteAction() === CalendarAdd::OVERWRITE_ACTION_REPLACE
            );

            if ('' === $request->get('submit', 'none')) {
                try {
                    $this->getDoctrine()->getManager()->flush();
                    $agreementRepository->updateDates($agreement);
                    $this->addFlash('success', $translator->trans('message.added', [], 'calendar'));
                    return $this->redirectToRoute('work_linked_training_agreement_calendar_list', [
                        'id' => $agreement->getId()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $translator->trans('message.save_error', [], 'calendar'));
                }
            }
        }

        $breadcrumb = [
            [
                'fixed' => (string) $agreement,
                'routeName' => 'work_linked_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar_add.html.twig', [
            'menu_path' => 'work_linked_training_agreement_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => $title,
            'calendar' => $workDays
        ]);
    }

    /**
     * @Route("/jornada/{id}", name="work_linked_training_agreement_calendar_form",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        WorkDay $workDay
    ) {
        $agreement = $workDay->getAgreement();
        $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));

        $form = $this->createForm(WorkDayType::class, $workDay);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.saved', [], 'calendar'));
                return $this->redirectToRoute('work_linked_training_agreement_calendar_list', [
                    'id' => $agreement->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'calendar'));
            }
        }

        $breadcrumb = [
            [
                'fixed' => (string) $agreement,
                'routeName' => 'work_linked_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar_form.html.twig', [
            'menu_path' => 'work_linked_training_agreement_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => $title
        ]);
    }

    /**
     * @Route("/{id}/eliminar", name="work_linked_training_agreement_calendar_delete",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        Agreement $agreement
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute(
                'work_linked_training_agreement_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        $workDays = $workDayRepository->findInListByIdAndAgreement($items, $agreement);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $workDayRepository->deleteFromList($workDays);

                $this->getDoctrine()->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.deleted', [], 'calendar'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'calendar'));
            }
            return $this->redirectToRoute(
                'work_linked_training_agreement_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        $title = $translator->trans('title.delete', [], 'calendar');

        $breadcrumb = [
            [
                'fixed' => (string) $agreement,
                'routeName' => 'work_linked_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar_delete.html.twig', [
            'menu_path' => 'work_linked_training_agreement_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'items' => $workDays
        ]);
    }
}
