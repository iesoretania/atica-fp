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

use AppBundle\Entity\WLT\ActivityRealizationGrade;
use AppBundle\Entity\WLT\Project;
use AppBundle\Form\Type\WLT\ActivityRealizationGradeType;
use AppBundle\Repository\WLT\ActivityRealizationGradeRepository;
use AppBundle\Security\WLT\ProjectVoter;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/acuerdo/calificacion")
 */
class ActivityRealizationGradeController extends Controller
{
    /**
     * @Route("/nueva/{id}",
     *     name="work_linked_training_activity_realization_grade_new", methods={"GET", "POST"})
     **/
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $activityRealizationGrade = new ActivityRealizationGrade();
        $activityRealizationGrade
            ->setProject($project);

        $this->getDoctrine()->getManager()->persist($activityRealizationGrade);

        return $this->formAction($request, $translator, $activityRealizationGrade);
    }

    /**
     * @Route("/{id}", name="work_linked_training_activity_realization_grade_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        ActivityRealizationGrade $activityRealizationGrade
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $activityRealizationGrade->getProject());

        $form = $this->createForm(ActivityRealizationGradeType::class, $activityRealizationGrade);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_activity_realization_grade'));
                return $this->redirectToRoute('work_linked_training_activity_realization_grade_list', [
                    'id' => $activityRealizationGrade->getProject()->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_activity_realization_grade'));
            }
        }

        $title = $translator->trans(
            $activityRealizationGrade->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_activity_realization_grade'
        );

        $breadcrumb = [
            [
                'fixed' => $activityRealizationGrade->getProject()->getName(),
                'routeName' => 'work_linked_training_project_list',
                'routeParams' => []
            ],
            [
                'fixed' => $translator->trans('title.list', [], 'wlt_activity_realization_grade'),
                'routeName' => 'work_linked_training_activity_realization_grade_list',
                'routeParams' => ['id' => $activityRealizationGrade->getProject()->getId()]
            ],
            $activityRealizationGrade->getId() ?
                ['fixed' => $activityRealizationGrade->getDescription()] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_activity_realization_grade')]
        ];

        return $this->render('wlt/grade/form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/listar/{page}/", name="work_linked_training_activity_realization_grade_list",
     *     requirements={"page" = "\d+"}, defaults={"page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        TranslatorInterface $translator,
        Project $project,
        $page = 1
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('arg')
            ->from(ActivityRealizationGrade::class, 'arg')
            ->orderBy('arg.numericGrade', 'DESC');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('arg.numericCode = :q')
                ->orWhere('arg.description LIKE :tq')
                ->orWhere('arg.notes LIKE :tq')
                ->setParameter('q', $q)
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('arg.project = :project')
            ->setParameter('project', $project);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wlt_activity_realization_grade');
        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_project_list',
                'routeParams' => []
            ],
            ['fixed' => $title]
        ];

        return $this->render('wlt/grade/list.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_activity_realization_grade',
            'project' => $project
        ]);
    }

    /**
     * @Route("/{id}/eliminar", name="work_linked_training_activity_realization_grade_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        ActivityRealizationGradeRepository $activityRealizationGradeRepository,
        TranslatorInterface $translator,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('work_linked_training_activity_realization_grade_list', [
                'id' => $project->getId()
            ]);
        }

        $grades = $activityRealizationGradeRepository->findAllInListByIdAndProject($items, $project);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $activityRealizationGradeRepository->deleteFromList($grades);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_activity_realization_grade'));
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $translator->trans('message.delete_error', [], 'wlt_activity_realization_grade')
                );
            }
            return $this->redirectToRoute('work_linked_training_activity_realization_grade_list', [
                'id' => $project->getId()
            ]);
        }

        $breadcrumb = [
            [
                'fixed' => $translator->trans('title.list', [], 'wlt_activity_realization_grade'),
                'routeName' => 'work_linked_training_activity_realization_grade_list',
                'routeParams' => []
            ],
            ['fixed' => $translator->trans('title.delete', [], 'wlt_activity_realization_grade')]
        ];

        return $this->render('wlt/grade/delete.html.twig', [
            'menu_path' => 'work_linked_training_evaluation_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'wlt_activity_realization_grade'),
            'items' => $grades
        ]);
    }
}
