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

namespace App\Controller\WPT;

use App\Entity\WPT\Agreement;
use App\Entity\WPT\WorkDay;
use App\Form\Model\CalendarAdd;
use App\Form\Type\WPT\CalendarAddType;
use App\Form\Type\WPT\WorkDayType;
use App\Repository\WPT\AgreementRepository;
use App\Repository\WPT\WorkDayRepository;
use App\Security\WPT\AgreementVoter;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/fct/convenio/calendario")
 */
class AgreementCalendarController extends AbstractController
{
    /**
     * @Route("/{id}", name="workplace_training_agreement_calendar_list",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function indexAction(
        WorkDayRepository $workDayRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Agreement $agreement
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);
        $readOnly = !$this->isGranted(AgreementVoter::MANAGE, $agreement);

        $workDaysData = $workDayRepository->findByAgreementGroupByMonthAndWeekNumber($agreement);

        $title = $translator->trans('title.calendar', [], 'wpt_agreement');

        $breadcrumb = [
            [
                'fixed' => $agreement->getShift()->getName(),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wpt_shift'),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $agreement->getWorkcenter(),
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/agreement/calendar.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'calendar' => $workDaysData,
            'read_only' => $readOnly
        ]);
    }

    /**
     * @Route("/{id}/incorporar", name="workplace_training_agreement_calendar_add",
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

        $title = $translator->trans('title.calendar.add', [], 'wpt_agreement');

        $totalHours = $workDayRepository->getTotalHoursByAgreement($agreement);

        $calendarAdd = new CalendarAdd();
        $calendarAdd
            ->setTotalHours(
                $agreement->getShift()->getHours() !== 0
                ? max(0, $agreement->getShift()->getHours() - $totalHours)
                : 0
            );
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
                    return $this->redirectToRoute('workplace_training_agreement_calendar_list', [
                        'id' => $agreement->getId()
                    ]);
                } catch (\Exception $e) {
                    $this->addFlash('error', $translator->trans('message.save_error', [], 'calendar'));
                }
            }
        }
        $breadcrumb = [
            [
                'fixed' => $agreement->getShift()->getName(),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wpt_shift'),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $agreement->getWorkcenter(),
                'routeName' => 'workplace_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/agreement/calendar_add.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => $title,
            'calendar' => $workDays
        ]);
    }

    /**
     * @Route("/jornada/{id}", name="workplace_training_agreement_calendar_form",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        WorkDay $workDay
    ) {
        $agreement = $workDay->getAgreement();

        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);
        $readOnly = !$this->isGranted(AgreementVoter::MANAGE, $agreement);

        $title = $translator->trans('dow' . ($workDay->getDate()->format('N') - 1), [], 'calendar');
        $title .= ' - ' . $workDay->getDate()->format($translator->trans('format.date', [], 'general'));

        $form = $this->createForm(WorkDayType::class, $workDay, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.saved', [], 'calendar'));
                return $this->redirectToRoute('workplace_training_agreement_calendar_list', [
                    'id' => $agreement->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'calendar'));
            }
        }
        $breadcrumb = [
            [
                'fixed' => $agreement->getShift()->getName(),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wpt_shift'),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $agreement->getWorkcenter(),
                'routeName' => 'workplace_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/agreement/calendar_form.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => $title,
            'read_only' => $readOnly
        ]);
    }

    /**
     * @Route("/{id}/eliminar", name="workplace_training_agreement_calendar_delete",
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
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute(
                'workplace_training_agreement_calendar_list',
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
                'workplace_training_agreement_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        $title = $translator->trans('title.delete', [], 'calendar');

        $breadcrumb = [
            [
                'fixed' => $agreement->getShift()->getName(),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wpt_shift'),
                'routeName' => 'workplace_training_agreement_list',
                'routeParams' => ['id' => $agreement->getShift()->getId()]
            ],
            [
                'fixed' => $agreement->getWorkcenter(),
                'routeName' => 'workplace_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/agreement/calendar_delete.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'items' => $workDays
        ]);
    }
}
