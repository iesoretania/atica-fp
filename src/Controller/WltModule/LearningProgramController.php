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

namespace App\Controller\WltModule;

use App\Entity\WltModule\LearningProgram;
use App\Entity\WltModule\Project;
use App\Form\Model\LearningProgramImport;
use App\Form\Type\WltModule\LearningProgramImportType;
use App\Form\Type\WltModule\LearningProgramType;
use App\Repository\WltModule\LearningProgramRepository;
use App\Security\WltModule\ProjectVoter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dual/programa')]
class LearningProgramController extends AbstractController
{
    #[Route(path: '/nuevo/{project}', name: 'work_linked_training_learning_program_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response
    {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $learningProgram = new LearningProgram();
        $learningProgram->setProject($project);
        $managerRegistry->getManager()->persist($learningProgram);

        return $this->index($request, $translator, $managerRegistry, $learningProgram);
    }

    #[Route(path: '/{id}', name: 'work_linked_training_learning_program_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        LearningProgram $learningProgram
    ): Response {
        $project = $learningProgram->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $em = $managerRegistry->getManager();

        $form = $this->createForm(LearningProgramType::class, $learningProgram, [
            'project' => $project
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_learning_program'));
                return $this->redirectToRoute('work_linked_training_learning_program_list', [
                    'project' => $project->getId(),
                    'page' => 1
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_learning_program'));
            }
        }

        $title = $translator->trans(
            $learningProgram->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wlt_learning_program'
        );

        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_learning_program_list',
                'routeParams' => ['project' => $project->getId()]
            ],
            $learningProgram->getId() !== null ?
                ['fixed' => $learningProgram->getCompany()] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_learning_program')]
        ];

        return $this->render('wlt/project/company/form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{project}/listar/{page}', name: 'work_linked_training_learning_program_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Project $project,
        int $page = 1
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('cp')
            ->addSelect('SIZE(cp.activityRealizations)')
            ->from(LearningProgram::class, 'cp')
            ->join('cp.company', 'c')
            ->addOrderBy('c.name');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('c.name LIKE :tq')
                ->orWhere('c.code LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('cp.project = :project')
            ->setParameter('project', $project);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'));
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }


        $title = $translator->trans('title.list', [], 'wlt_learning_program');

        $breadcrumb = [
            [
                'fixed' => $project->getName(),
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/project/company/list.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_learning_program',
            'project' => $project
        ]);
    }

    #[Route(path: '/operacion/{project}', name: 'work_linked_training_learning_program_operation', methods: ['POST'])]
    public function operation(
        Request $request,
        TranslatorInterface $translator,
        LearningProgramRepository $learningProgramRepository,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response|RedirectResponse {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $items = $request->request->all('items');

        if (count($items) !== 0 && '' === $request->get('delete')) {
            return $this->delete($items, $request, $translator, $learningProgramRepository, $managerRegistry, $project);
        }

        return $this->redirectToRoute(
            'work_linked_training_learning_program_list',
            ['project' => $project->getId()]
        );
    }

    private function delete(
        $items,
        Request $request,
        TranslatorInterface $translator,
        LearningProgramRepository $learningProgramRepository,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response {
        $em = $managerRegistry->getManager();

        $learningPrograms = $learningProgramRepository->findAllInListByIdAndProject($items, $project);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $learningProgramRepository->deleteFromList($learningPrograms);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_learning_program'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_learning_program'));
            }
            return $this->redirectToRoute(
                'work_linked_training_learning_program_list',
                ['project' => $project->getId()]
            );
        }

        return $this->render('wlt/project/company/delete.html.twig', [
            'menu_path' => 'work_linked_training_learning_program_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'wlt_learning_program')]],
            'title' => $translator->trans('title.delete', [], 'wlt_learning_program'),
            'items' => $learningPrograms
        ]);
    }
}
