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

namespace App\Controller\WPT;

use App\Entity\WPT\Activity;
use App\Entity\WPT\Shift;
use App\Form\Model\WPT\ActivityCopy;
use App\Form\Model\WPT\ActivityCopyFromWLT;
use App\Form\Type\WPT\ActivityCopyFromWLTType;
use App\Form\Type\WPT\ActivityCopyType;
use App\Form\Type\WPT\ActivityType;
use App\Repository\WLT\ProjectRepository;
use App\Repository\WPT\ActivityRepository;
use App\Repository\WPT\ShiftRepository;
use App\Security\WLT\WLTOrganizationVoter;
use App\Security\WPT\ShiftVoter;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/fct/actividad')]
class ActivityController extends AbstractController
{
    #[Route(path: '/nueva/{shift}', name: 'workplace_training_activity_new', requirements: ['shift' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);

        $activity = new Activity();
        $activity
            ->setShift($shift);

        $managerRegistry->getManager()->persist($activity);

        return $this->index(
            $request,
            $userExtensionService,
            $translator,
            $managerRegistry,
            $activity
        );
    }

    #[Route(path: '/{id}', name: 'workplace_training_activity_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Activity $activity
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);
        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $activity->getShift());

        $em = $managerRegistry->getManager();

        $form = $this->createForm(ActivityType::class, $activity, [
            'shift' => $activity->getShift()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_activity'));
                return $this->redirectToRoute('workplace_training_activity_list', [
                    'id' => $activity->getShift()->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_activity'));
            }
        }

        $title = $translator->trans(
            $activity->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wpt_activity'
        );

        $breadcrumb = [
            [
                'fixed' => $activity->getShift()->getName(),
                'routeName' => 'workplace_training_shift_list',
                'routeParams' => ['id' => $activity->getShift()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.list', [], 'wpt_activity'),
                'routeName' => 'workplace_training_activity_list',
                'routeParams' => ['id' => $activity->getShift()->getId()]
            ],
            $activity->getId() !== null ?
                ['fixed' => $activity->getCode()] :
                ['fixed' => $translator->trans('title.new', [], 'wpt_activity')]
        ];

        return $this->render('wpt/activity/form.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'activity' => $activity
        ]);
    }

    #[Route(path: '/{id}/listar/{page}', name: 'workplace_training_activity_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Shift $shift,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);
        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);

        if ($shift->getGrade()->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->from(Activity::class, 'a')
            ->orderBy('a.code');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('a.code LIKE :tq')
                ->orWhere('a.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('a.shift = :shift')
            ->setParameter('shift', $shift);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wpt_activity');

        $breadcrumb = [
            [
                'fixed' => $shift->getName(),
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/activity/list.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_activity',
            'shift' => $shift
        ]);
    }

    #[Route(path: '/operacion/{shift}', name: 'workplace_training_activity_operation', requirements: ['shift' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ActivityRepository $activityRepository,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);
        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);

        $items = $request->request->all('items');

        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('workplace_training_activity_list', ['id' => $shift->getId()]);
        }

        $selectedItems = $activityRepository->findAllInListByIdAndShift($items, $shift);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $activityRepository->deleteFromList($selectedItems);
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wpt_activity'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wpt_activity'));
            }
            return $this->redirectToRoute('workplace_training_activity_list', ['id' => $shift->getId()]);
        }
        $title = $translator->trans('title.delete', [], 'wpt_activity');

        $breadcrumb = [
            [
                'fixed' => $shift->getName(),
                'routeName' => 'workplace_training_activity_list',
                'routeParams' => ['id' => $shift->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/activity/delete.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }

    #[Route(path: '/importar/{id}', name: 'workplace_training_activity_import', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function import(
        Request $request,
        ActivityRepository $activityRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);
        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);

        $em = $managerRegistry->getManager();

        $lines = trim($request->request->get('data', []));
        if ($lines === '') {
            return $this->redirectToRoute('workplace_training_activity_list', ['id' => $shift->getId()]);
        }

        $items = $this->parseImport($lines);
        foreach ($items as $code => $item) {
            $activity = $activityRepository->findOneByCodeAndShift($code, $shift);
            if (null === $activity) {
                $activity = new Activity();
                $em->persist($activity);
            }
            $activity
                ->setShift($shift)
                ->setCode($code)
                ->setDescription($item);
        }
        try {
            $em->flush();
            $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_activity'));
        } catch (\Exception) {
            $this->addFlash('error', $translator->trans('message.error', [], 'wpt_activity'));
        }
        return $this->redirectToRoute('workplace_training_activity_list', ['id' => $shift->getId()]);
    }

    /**
     * @param $lines
     */
    private function parseImport(string $lines): array
    {
        $items = explode("\n", $lines);
        $output = [];
        $matches = [];

        foreach ($items as $item) {
            preg_match('/^(.{1,10}): (.*)/u', $item, $matches);
            if ($matches !== []) {
                $output[$matches[1]] = $matches[2];
            }
        }

        return $output;
    }

    #[Route(path: '/copiar/{id}', name: 'workplace_training_activity_copy', methods: ['GET', 'POST'])]
    public function copy(
        Request $request,
        UserExtensionService $userExtensionService,
        ShiftRepository $shiftRepository,
        ActivityRepository $activityRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);
        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);

        $shifts = $shiftRepository->findRelatedByOrganizationButOne($organization, $shift);

        $activityCopy = new ActivityCopy();
        $form = $this->createForm(ActivityCopyType::class, $activityCopy, [
            'shifts' => $shifts
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // copiar datos de la convocatoria seleccionada
            try {
                $activityRepository->copyFromShift(
                    $shift,
                    $activityCopy->getShift()
                );

                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.copied', [], 'wpt_activity'));

                return $this->redirectToRoute('workplace_training_activity_list', ['id' => $shift->getId()]);
            } catch (\Exception) {
                $this->addFlash(
                    'error',
                    $translator->trans('message.copy_error', [], 'wpt_activity')
                );
            }
        }

        $title = $translator->trans('title.copy', [], 'wpt_activity');

        $breadcrumb = [
            [
                'fixed' => $shift->getName(),
                'routeName' => 'workplace_training_activity_list',
                'routeParams' => ['id' => $shift->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/activity/copy.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'shift' => $shift
        ]);
    }

    #[Route(path: '/copiar/dual/{id}', name: 'workplace_training_activity_worklinked_copy', methods: ['GET', 'POST'])]
    public function copyWlt(
        Request $request,
        UserExtensionService $userExtensionService,
        ProjectRepository $projectRepository,
        ActivityRepository $activityRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Shift $shift
    ): Response {
        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        $projects = $projectRepository->findByAcademicYear($shift->getGrade()->getTraining()->getAcademicYear());

        $activityCopyFromWlt = new ActivityCopyFromWLT();
        $form = $this->createForm(ActivityCopyFromWLTType::class, $activityCopyFromWlt, [
            'projects' => $projects
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // copiar datos del proyecto seleccionado
            try {
                $activityRepository->copyFromWLTProject(
                    $shift,
                    $activityCopyFromWlt->getProject()
                );

                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.copied', [], 'wpt_activity'));

                return $this->redirectToRoute('workplace_training_activity_list', ['id' => $shift->getId()]);
            } catch (\Exception) {
                $this->addFlash(
                    'error',
                    $translator->trans('message.copy_error', [], 'wpt_activity')
                );
            }
        }

        $title = $translator->trans('title.copy_from_wlt', [], 'wpt_activity');

        $breadcrumb = [
            [
                'fixed' => $shift->getName(),
                'routeName' => 'workplace_training_activity_list',
                'routeParams' => ['id' => $shift->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/activity/copy_from_wlt.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'shift' => $shift
        ]);
    }
}
