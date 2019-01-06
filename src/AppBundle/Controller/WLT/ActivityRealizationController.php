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

use AppBundle\Entity\WLT\Activity;
use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Form\Type\WLT\ActivityRealizationType;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Security\Edu\TrainingVoter;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/ensenanza")
 */
class ActivityRealizationController extends Controller
{
    /**
     * @Route("/materia/actividad/{id}/concrecion/nueva", name="work_linked_training_training_activity_realization_new",
     *     methods={"GET", "POST"})
     **/
    public function newAction(Request $request, TranslatorInterface $translator, Activity $activity)
    {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $activity->getSubject()->getGrade()->getTraining());

        $activityRealization = new ActivityRealization();
        $activityRealization
            ->setActivity($activity)
            ->setCode($activity->getCode());

        $this->getDoctrine()->getManager()->persist($activityRealization);

        return $this->formAction($request, $translator, $activityRealization);
    }

    /**
     * @Route("/materia/actividad/concrecion/{id}", name="work_linked_training_training_activity_realization_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        ActivityRealization $activityRealization
    ) {
        $activity = $activityRealization->getActivity();
        $subject = $activity->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $form = $this->createForm(ActivityRealizationType::class, $activityRealization, [
            'activity' => $activity
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_activity_realization'));
                return $this->redirectToRoute('work_linked_training_training_activity_realization_list', [
                    'id' => $activity->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_activity_realization'));
            }
        }

        $title = $translator->trans(
            $activityRealization->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_activity_realization'
        );

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'work_linked_training_training_subject_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            [
                'fixed' => $subject->getName(),
                'routeName' => 'work_linked_training_training_activity_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            [
                'fixed' => $activity->getCode(),
                'routeName' => 'work_linked_training_training_activity_realization_list',
                'routeParams' => ['id' => $activity->getId()]
            ],
            $activityRealization->getId() ?
                ['fixed' => $activityRealization->getCode()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'wlt_activity_realization')]
        ];

        return $this->render('wlt/training/activity_realization_form.html.twig', [
            'menu_path' => 'work_linked_training_training',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/materia/actividad/{id}/concrecion/{page}/", name="work_linked_training_training_activity_realization_list",
     *     requirements={"id" = "\d+", "page" = "\d+"}, defaults={"page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        TranslatorInterface $translator,
        Activity $activity,
        $page = 1
    ) {
        $subject = $activity->getSubject();
        $training = $subject->getGrade()->getTraining();
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('ar')
            ->from(ActivityRealization::class, 'ar')
            ->orderBy('ar.code');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('ar.code LIKE :tq')
                ->orWhere('ar.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('ar.activity = :activity')
            ->setParameter('activity', $activity);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $activity->getCode() . ' - ' . $translator->trans('title.list', [], 'wlt_activity_realization');

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'work_linked_training_training_subject_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            [
                'fixed' => $subject->getName(),
                'routeName' => 'work_linked_training_training_activity_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            [
                'fixed' => $activity->getCode(),
            ],
            ['fixed' => $translator->trans('title.list', [], 'wlt_activity_realization')]
        ];

        return $this->render('wlt/training/activity_realization_list.html.twig', [
            'menu_path' => 'work_linked_training_training',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'activity' => $activity,
            'domain' => 'wlt_activity_realization'
        ]);
    }

    /**
     * @Route("/materia/actividad/concrecion/eliminar/{id}", name="work_linked_training_training_activity_realization_delete",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        ActivityRealizationRepository $activityRealizationRepository,
        TranslatorInterface $translator,
        Activity $activity)
    {
        $training = $activity->getSubject()->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('work_linked_training_training_activity_realization_list', ['id' => $activity->getId()]);
        }

        $activityRealizations = $activityRealizationRepository->findAllInListByIdAndActivity($items, $activity);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $activityRealizationRepository->deleteFromList($activityRealizations);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_activity_realization'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_activity_realization'));
            }
            return $this->redirectToRoute('work_linked_training_training_activity_realization_list', ['id' => $activity->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'work_linked_training_training_subject_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            [
                'fixed' => $activity->getSubject()->getName(),
                'routeName' => 'work_linked_training_training_activity_list',
                'routeParams' => ['id' => $activity->getSubject()->getId()]
            ],
            [
                'fixed' => $activity->getCode(),
                'routeName' => 'work_linked_training_training_activity_realization_list',
                'routeParams' => ['id' => $activity->getId()]
            ],
            ['fixed' => $this->get('translator')->trans('title.delete', [], 'wlt_activity_realization')]
        ];

        return $this->render('wlt/training/activity_delete.html.twig', [
            'menu_path' => 'training',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'wlt_activity_realization'),
            'items' => $activityRealizations
        ]);
    }
}
