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

namespace AppBundle\Controller\Organization;

use AppBundle\Entity\Edu\LearningOutcome;
use AppBundle\Entity\Edu\Subject;
use AppBundle\Form\Type\Edu\LearningOutcomeType;
use AppBundle\Repository\Edu\LearningOutcomeRepository;
use AppBundle\Security\Edu\TrainingVoter;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/centro/ensenanza")
 */
class LearningOutcomeController extends Controller
{
    /**
     * @Route("/materia/{id}/resultado/nuevo", name="organization_training_learning_outcome_new",
     *     methods={"GET", "POST"})
     **/
    public function newAction(Request $request, TranslatorInterface $translator, Subject $subject)
    {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        $learningOutcome = new LearningOutcome();
        $learningOutcome
            ->setSubject($subject);

        $this->getDoctrine()->getManager()->persist($learningOutcome);

        return $this->formAction($request, $translator, $learningOutcome);
    }

    /**
     * @Route("/materia/resultado/{id}", name="organization_training_learning_outcome_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        LearningOutcome $learningOutcome
    ) {
        $subject = $learningOutcome->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $form = $this->createForm(LearningOutcomeType::class, $learningOutcome, [
            'subject' => $subject
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_learning_outcome'));
                return $this->redirectToRoute('organization_training_learning_outcome_list', [
                    'id' => $subject->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_learning_outcome'));
            }
        }

        $title = $translator->trans(
            $learningOutcome->getId() ? 'title.edit' : 'title.new',
            [],
            'edu_learning_outcome'
        );

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'organization_subject_list',
                'routeParams' => []
            ],
            [
                'fixed' => $subject->getName(),
                'routeName' => 'organization_training_learning_outcome_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            $learningOutcome->getId() ?
                ['fixed' => $learningOutcome->getCode()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'edu_learning_outcome')]
        ];

        return $this->render('organization/training/learning_outcome_form.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'subject' => $subject,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/materia/{id}/resultado/{page}/", name="organization_training_learning_outcome_list",
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
            ->select('l')
            ->from(LearningOutcome::class, 'l')
            ->orderBy('l.code');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('l.code LIKE :tq')
                ->orWhere('l.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('l.subject = :subject')
            ->setParameter('subject', $subject);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $subject->getName() . ' - ' . $translator->trans('title.list', [], 'edu_learning_outcome');

        $breadcrumb = [
            [
                'fixed' => $subject->getGrade()->getTraining()->getName(),
            ],
            ['fixed' => $subject->getName()],
            ['fixed' => $translator->trans('title.list', [], 'edu_learning_outcome')]
        ];

        return $this->render('organization/training/learning_outcome_list.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'subject' => $subject,
            'domain' => 'edu_learning_outcome'
        ]);
    }

    /**
     * @Route("/materia/resultado/eliminar/{id}", name="organization_training_learning_outcome_delete",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        LearningOutcomeRepository $learningOutcomeRepository,
        TranslatorInterface $translator,
        Subject $subject
    ) {
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_training_learning_outcome_list', ['id' => $subject->getId()]);
        }

        $learningOutcomes = $learningOutcomeRepository->findAllInListByIdAndSubject($items, $subject);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $learningOutcomeRepository->deleteFromList($learningOutcomes);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_learning_outcome'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_learning_outcome'));
            }
            return $this->redirectToRoute('organization_training_learning_outcome_list', ['id' => $subject->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $training->getName(),
                'routeName' => 'organization_training_learning_outcome_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            [
                'fixed' => $subject->getName(),
                'routeName' => 'organization_training_learning_outcome_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            ['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_learning_outcome')]
        ];

        return $this->render('organization/training/learning_outcome_delete.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'edu_learning_outcome'),
            'learning_outcomes' => $learningOutcomes
        ]);
    }

    /**
     * @Route("/materia/resultado/importar/{id}", name="organization_training_learning_outcome_import",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function importAction(
        Request $request,
        LearningOutcomeRepository $learningOutcomeRepository,
        TranslatorInterface $translator,
        Subject $subject
    ) {
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $this->getDoctrine()->getManager();

        $lines = trim($request->request->get('data', []));
        if ($lines === '') {
            return $this->redirectToRoute('organization_training_learning_outcome_list', ['id' => $subject->getId()]);
        }

        $items = $this->parseImport($lines);
        foreach ($items as $code => $item) {
            if (null === $learningOutcomeRepository->findOneByCodeAndSubject($code, $subject)) {
                $learningOutcome = new LearningOutcome();
                $learningOutcome
                    ->setSubject($subject)
                    ->setCode($code)
                    ->setDescription($item);
                $em->persist($learningOutcome);
            }
        }
        try {
            $em->flush();
            $this->addFlash('success', $translator->trans('message.saved', [], 'edu_learning_outcome'));
        } catch (\Exception $e) {
            $this->addFlash('error', $translator->trans('message.error', [], 'edu_learning_outcome'));
        }
        return $this->redirectToRoute('organization_training_learning_outcome_list', ['id' => $subject->getId()]);
    }

    /**
     * @Route("/materia/resultado/exportar/{id}", name="organization_training_learning_outcome_export",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function exportAction(
        Subject $subject
    ) {
        $training = $subject->getGrade()->getTraining();
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $data = '';

        foreach ($subject->getLearningOutcomes() as $learningOutcome) {
            $lines = explode("\n", $learningOutcome->getDescription());
            foreach ($lines as &$line) {
                $line = trim($line);
            }
            $data .= $learningOutcome->getCode() . ': ' . implode(' ', $lines) . "\n";
        }

        return new Response(
            $data,
            Response::HTTP_OK,
            array('content-type' => 'text/plain')
        );
    }

    /**
     * @param $lines
     *
     * @return array
     */
    private function parseImport($lines)
    {
        $items = explode("\n", $lines);
        $output = [];
        $matches = [];

        foreach ($items as $item) {
            preg_match('/^(.{1,10})\: (.*)/u', $item, $matches);
            if ($matches) {
                $output[$matches[1]] = $matches[2];
            }
        }

        return $output;
    }
}
