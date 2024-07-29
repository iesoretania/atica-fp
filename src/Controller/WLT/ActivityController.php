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

use App\Entity\WLT\Activity;
use App\Entity\WLT\Project;
use App\Form\Model\WLT\ActivityCopy;
use App\Form\Type\WLT\ActivityCopyType;
use App\Form\Type\WLT\ActivityType;
use App\Repository\WLT\ActivityRepository;
use App\Repository\WLT\LearningProgramRepository;
use App\Repository\WLT\ProjectRepository;
use App\Security\WLT\ProjectVoter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/dual/proyecto")
 */
class ActivityController extends AbstractController
{
    /**
     * @Route("/programa/{id}/actividad/nueva",
     *     name="work_linked_training_training_activity_new", methods={"GET", "POST"})
     **/
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $activity = new Activity();
        $activity
            ->setProject($project);

        $managerRegistry->getManager()->persist($activity);

        return $this->formAction($request, $translator, $managerRegistry, $activity);
    }

    /**
     * @Route("/programa/actividad/{id}", name="work_linked_training_training_activity_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Activity $activity
    ) {
        $project = $activity->getProject();

        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $form = $this->createForm(ActivityType::class, $activity, [
            'project' => $project
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_activity'));
                return $this->redirectToRoute('work_linked_training_project_activity_list', [
                    'id' => $project->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_activity'));
            }
        }

        $title = $translator->trans(
            $activity->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wlt_activity'
        );

        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_project_activity_list',
                'routeParams' => ['id' => $project->getId()]
            ],
            ['fixed' => $translator->trans(
                $activity->getId() !== null ? 'title.edit' : 'title.new',
                [],
                'wlt_activity'
            )]
        ];

        return $this->render('wlt/training/activity_form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'subject' => $project,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/programa/{id}/actividad/{page}", name="work_linked_training_project_activity_list",
     *     requirements={"id" = "\d+", "page" = "\d+"}, defaults={"page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Project $project,
        $page = 1
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

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
            ->andWhere('a.project = :project')
            ->setParameter('project', $project);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $project->getName() . ' - ' . $translator->trans('title.list', [], 'wlt_activity');

        $breadcrumb = [
            ['fixed' => $project->getName()],
            ['fixed' => $translator->trans('title.list', [], 'wlt_activity')]
        ];

        return $this->render('wlt/training/activity_list.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'project' => $project,
            'domain' => 'wlt_activity'
        ]);
    }

    /**
     * @Route("/programa/{id}/actividad/eliminar", name="work_linked_training_training_activity_delete",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        ActivityRepository $activityRepository,
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('work_linked_training_project_activity_list', ['id' => $project->getId()]);
        }

        $activities = $activityRepository->findAllInListByIdAndProject($items, $project);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $activityRepository->deleteFromList($activities);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_activity'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_activity'));
            }
            return $this->redirectToRoute('work_linked_training_project_activity_list', ['id' => $project->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_project_activity_list',
                'routeParams' => ['id' => $project->getId()]
            ],
            [
                'fixed' => $translator->trans('title.delete', [], 'wlt_activity')
            ]
        ];

        return $this->render('wlt/training/activity_delete.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'wlt_activity'),
            'items' => $activities
        ]);
    }

    /**
     * @Route("/copiar/{id}", name="work_linked_training_training_activity_copy", methods={"GET", "POST"})
     */
    public function copyAction(
        Request $request,
        ProjectRepository $projectRepository,
        ActivityRepository $activityRepository,
        LearningProgramRepository $learningProgramRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $projects = $projectRepository->findRelatedByOrganizationButOne($project->getOrganization(), $project);

        $activityCopy = new ActivityCopy();
        $form = $this->createForm(ActivityCopyType::class, $activityCopy, [
            'projects' => $projects
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // copiar datos del proyecto seleccionado
            try {
                $activityRepository->copyFromProject(
                    $project,
                    $activityCopy->getProject()
                );
                $managerRegistry->getManager()->flush();
                if ($activityCopy->getCopyLearningProgram()) {
                    $learningProgramRepository->copyFromProject(
                        $project,
                        $activityCopy->getProject()
                    );
                }

                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.copied', [], 'wlt_activity'));

                return $this->redirectToRoute(
                    'work_linked_training_project_activity_list',
                    ['id' => $project->getId()]
                );
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $translator->trans('message.copy_error', [], 'wlt_activity')
                );
            }
        }

        $title = $translator->trans('title.copy', [], 'wlt_activity');

        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_project_activity_list',
                'routeParams' => ['id' => $project->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/training/copy.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'project' => $project
        ]);
    }
}
