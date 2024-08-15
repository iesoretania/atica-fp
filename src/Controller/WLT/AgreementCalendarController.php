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

use App\Entity\WLT\Agreement;
use App\Entity\WLT\WorkDay;
use App\Form\Model\CalendarAdd;
use App\Form\Type\WLT\CalendarAddType;
use App\Form\Type\WLT\WorkDayType;
use App\Repository\WLT\AgreementRepository;
use App\Repository\WLT\WorkDayRepository;
use App\Security\WLT\AgreementVoter;
use App\Security\WLT\WLTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dual/acuerdo/calendario')]
class AgreementCalendarController extends AbstractController
{
    #[Route(path: '/{id}', name: 'work_linked_training_agreement_calendar_list', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function index(
        WorkDayRepository $workDayRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Agreement $agreement
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);
        $this->denyAccessUnlessGranted(AgreementVoter::ACCESS, $agreement);
        $readOnly = !$this->isGranted(AgreementVoter::MANAGE, $agreement);

        $workDaysData = $workDayRepository->findByAgreementGroupByMonthAndWeekNumber($agreement);

        $title = $translator->trans('title.calendar', [], 'wlt_agreement');

        $breadcrumb = [
            [
                'fixed' => $agreement->getProject()->getName(),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wlt_project'),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => (string) $agreement,
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'calendar' => $workDaysData,
            'read_only' => $readOnly
        ]);
    }

    #[Route(path: '/{id}/incorporar', name: 'work_linked_training_agreement_calendar_add', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function add(
        Request $request,
        TranslatorInterface $translator,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        ManagerRegistry $managerRegistry,
        Agreement $agreement
    ): Response {
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
                $calendarAdd->getOverwriteAction() === CalendarAdd::OVERWRITE_ACTION_REPLACE,
                $calendarAdd->getIgnoreNonWorkingDays()
            );

            if ('' === $request->get('submit', 'none')) {
                try {
                    $managerRegistry->getManager()->flush();
                    $agreementRepository->updateDates($agreement);
                    $this->addFlash('success', $translator->trans('message.added', [], 'calendar'));
                    return $this->redirectToRoute('work_linked_training_agreement_calendar_list', [
                        'id' => $agreement->getId()
                    ]);
                } catch (\Exception) {
                    $this->addFlash('error', $translator->trans('message.save_error', [], 'calendar'));
                }
            }
        }
        $breadcrumb = [
            [
                'fixed' => $agreement->getProject()->getName(),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wlt_project'),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => (string) $agreement,
                'routeName' => 'work_linked_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar_add.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => $title,
            'calendar' => $workDays
        ]);
    }

    #[Route(path: '/jornada/{id}', name: 'work_linked_training_agreement_calendar_form', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        WorkDay $workDay
    ): Response {
        $agreement = $workDay->getAgreement();

        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);
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
                $managerRegistry->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.saved', [], 'calendar'));
                return $this->redirectToRoute('work_linked_training_agreement_calendar_list', [
                    'id' => $agreement->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'calendar'));
            }
        }
        $breadcrumb = [
            [
                'fixed' => $agreement->getProject()->getName(),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wlt_project'),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => (string) $agreement,
                'routeName' => 'work_linked_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar_form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'title' => $title,
            'read_only' => $readOnly
        ]);
    }

    #[Route(path: '/{id}/eliminar', name: 'work_linked_training_agreement_calendar_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Agreement $agreement
    ): Response {
        $this->denyAccessUnlessGranted(AgreementVoter::MANAGE, $agreement);

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute(
                'work_linked_training_agreement_calendar_list',
                ['id' => $agreement->getId()]
            );
        }

        $workDays = $workDayRepository->findInListByIdAndAgreement($items, $agreement);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $workDayRepository->deleteFromList($workDays);

                $managerRegistry->getManager()->flush();
                $agreementRepository->updateDates($agreement);
                $this->addFlash('success', $translator->trans('message.deleted', [], 'calendar'));
            } catch (\Exception) {
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
                'fixed' => $agreement->getProject()->getName(),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.agreements', [], 'wlt_project'),
                'routeName' => 'work_linked_training_agreement_list',
                'routeParams' => ['id' => $agreement->getProject()->getId()]
            ],
            [
                'fixed' => (string) $agreement,
                'routeName' => 'work_linked_training_agreement_calendar_list',
                'routeParams' => ['id' => $agreement->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/agreement/calendar_delete.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'agreement' => $agreement,
            'items' => $workDays
        ]);
    }
}
