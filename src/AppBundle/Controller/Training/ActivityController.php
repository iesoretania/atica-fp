<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

namespace AppBundle\Controller\Training;

use AppBundle\Entity\Edu\Subject;
use AppBundle\Entity\WLT\Activity;
use AppBundle\Form\Type\WLT\ActivityType;
use AppBundle\Repository\WLT\ActivityRepository;
use AppBundle\Security\Edu\TrainingVoter;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/ensenanza")
 */
class ActivityController extends Controller
{
    /**
     * @Route("/materia/{id}/actividad/nueva", name="training_activity_new", methods={"GET", "POST"})
     **/
    public function newAction(Request $request, TranslatorInterface $translator, Subject $subject)
    {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        $activity = new Activity();
        $activity
            ->setSubject($subject);

        $this->getDoctrine()->getManager()->persist($activity);

        return $this->formAction($request, $translator, $activity);
    }

    /**
     * @Route("/materia/actividad/{id}", name="training_activity_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        Activity $activity
    ) {
        $subject = $activity->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $form = $this->createForm(ActivityType::class, $activity, [
            'subject' => $subject
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_activity'));
                return $this->redirectToRoute('training_activity_list', [
                    'id' => $subject->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_activity'));
            }
        }

        $title = $translator->trans(
            $activity->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_activity'
        );

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'training_subject_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            [
                'fixed' => $subject->getName(),
                'routeName' => 'training_activity_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            $activity->getId() ?
                ['fixed' => $activity->getCode()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'wlt_activity')]
        ];

        return $this->render('training/activity_form.html.twig', [
            'menu_path' => 'training',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'subject' => $subject,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/materia/{id}/actividad/{page}/", name="training_activity_list",
     *     requirements={"id" = "\d+", "page" = "\d+"}, defaults={"page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        TranslatorInterface $translator,
        Subject $subject,
        $page = 1
    ) {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('a')
            ->from(Activity::class, 'a')
            ->orderBy('a.code');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('a.code LIKE :tq')
                ->orWhere('a.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('a.subject = :subject')
            ->setParameter('subject', $subject);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $subject->getName() . ' - ' . $translator->trans('title.list', [], 'wlt_activity');

        $breadcrumb = [
            [
                'fixed' => $subject->getGrade()->getTraining()->getName(),
                'routeName' => 'training_subject_list',
                'routeParams' => ['id' => $subject->getGrade()->getTraining()->getId()]
            ],
            ['fixed' => $subject->getName()],
            ['fixed' => $translator->trans('title.list', [], 'wlt_activity')]
        ];

        return $this->render('training/activity_list.html.twig', [
            'menu_path' => 'training',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'subject' => $subject,
            'domain' => 'wlt_activity'
        ]);
    }

    /**
     * @Route("/materia/actividad/eliminar/{id}", name="training_activity_delete",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        ActivityRepository $activityRepository,
        TranslatorInterface $translator,
        Subject $subject)
    {
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('training_activity_list', ['id' => $subject->getId()]);
        }

        $activities = $activityRepository->findAllInListByIdAndSubject($items, $subject);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $activityRepository->deleteFromList($activities);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_activity'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_activity'));
            }
            return $this->redirectToRoute('training_activity_list', ['id' => $subject->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'training_subject_list',
                'routeParams' => ['id' => $training->getId()]
            ],
            [
                'fixed' => $subject->getName(),
                'routeName' => 'training_activity_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            ['fixed' => $this->get('translator')->trans('title.delete', [], 'wlt_activity')]
        ];

        return $this->render('training/activity_delete.html.twig', [
            'menu_path' => 'training',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'wlt_activity'),
            'items' => $activities
        ]);
    }
}
