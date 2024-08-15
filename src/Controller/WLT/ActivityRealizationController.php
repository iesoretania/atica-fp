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

namespace App\Controller\WLT;

use App\Entity\WLT\Activity;
use App\Entity\WLT\ActivityRealization;
use App\Form\Type\WLT\ActivityRealizationType;
use App\Repository\WLT\ActivityRealizationRepository;
use App\Security\WLT\ProjectVoter;
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

#[Route(path: '/dual/proyecto')]
class ActivityRealizationController extends AbstractController
{
    #[Route(path: '/programa/{id}/concrecion/nueva', name: 'work_linked_training_project_activity_realization_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Activity $activity
    ): Response
    {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $activity->getProject());

        $activityRealization = new ActivityRealization();
        $activityRealization
            ->setActivity($activity)
            ->setCode($activity->getCode());

        $managerRegistry->getManager()->persist($activityRealization);

        return $this->form($request, $translator, $managerRegistry, $activityRealization);
    }

    #[Route(path: '/programa/{id}/detalles/concrecion', name: 'work_linked_training_project_activity_realization_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        ActivityRealization $activityRealization
    ): Response {
        $activity = $activityRealization->getActivity();
        $project = $activity->getProject();

        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $form = $this->createForm(ActivityRealizationType::class, $activityRealization, [
            'activity' => $activity
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_activity_realization'));
                return $this->redirectToRoute('work_linked_training_project_activity_realization_list', [
                    'id' => $activity->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_activity_realization'));
            }
        }

        $title = $translator->trans(
            $activityRealization->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wlt_activity_realization'
        );

        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_project_activity_list',
                'routeParams' => ['id' => $project->getId()]
            ],
            [
                'fixed' => $activity->getCode(),
                'routeName' => 'work_linked_training_project_activity_realization_list',
                'routeParams' => ['id' => $activity->getId()]
            ],
            $activityRealization->getId() !== null ?
                ['fixed' => $activityRealization->getCode()] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_activity_realization')]
        ];

        return $this->render('wlt/training/activity_realization_form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/programa/{id}/concrecion/{page}', name: 'work_linked_training_project_activity_realization_list', requirements: ['id' => '\d+', 'page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Activity $activity,
        int $page = 1
    ): Response {
        $project = $activity->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('ar')
            ->from(ActivityRealization::class, 'ar')
            ->orderBy('ar.code');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('ar.code LIKE :tq')
                ->orWhere('ar.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('ar.activity = :activity')
            ->setParameter('activity', $activity);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $activity->getCode() . ' - ' . $translator->trans('title.list', [], 'wlt_activity_realization');

        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_project_activity_list',
                'routeParams' => ['id' => $project->getId()]
            ],
            [
                'fixed' => $activity->getCode(),
            ],
            ['fixed' => $translator->trans('title.list', [], 'wlt_activity_realization')]
        ];

        return $this->render('wlt/training/activity_realization_list.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'activity' => $activity,
            'domain' => 'wlt_activity_realization'
        ]);
    }

    #[Route(path: '/programa/{id}/eliminar/concrecion', name: 'work_linked_training_project_activity_realization_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        ActivityRealizationRepository $activityRealizationRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Activity $activity): Response
    {
        $project = $activity->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $em = $managerRegistry->getManager();

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute(
                'work_linked_training_project_activity_realization_list',
                ['id' => $activity->getId()]
            );
        }

        $activityRealizations = $activityRealizationRepository->findAllInListByIdAndActivity($items, $activity);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $activityRealizationRepository->deleteFromList($activityRealizations);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_activity_realization'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_activity_realization'));
            }
            return $this->redirectToRoute('work_linked_training_project_activity_realization_list', ['id' => $activity->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_project_activity_list',
                'routeParams' => ['id' => $project->getId()]
            ],
            [
                'fixed' => $activity->getCode(),
                'routeName' => 'work_linked_training_project_activity_realization_list',
                'routeParams' => ['id' => $activity->getId()]
            ],
            ['fixed' => $translator->trans('title.delete', [], 'wlt_activity_realization')]
        ];

        return $this->render('wlt/training/activity_delete.html.twig', [
            'menu_path' => 'training',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'wlt_activity_realization'),
            'items' => $activityRealizations
        ]);
    }
}
