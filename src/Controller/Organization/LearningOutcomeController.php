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

namespace App\Controller\Organization;

use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Subject;
use App\Form\Type\Edu\LearningOutcomeType;
use App\Repository\Edu\LearningOutcomeRepository;
use App\Security\Edu\TrainingVoter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/ensenanza')]
class LearningOutcomeController extends AbstractController
{
    #[Route(path: '/materia/{id}/resultado/nuevo', name: 'organization_training_learning_outcome_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Subject $subject
    ) {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        $learningOutcome = new LearningOutcome();
        $learningOutcome
            ->setSubject($subject);

        $managerRegistry->getManager()->persist($learningOutcome);

        return $this->formAction($request, $translator, $managerRegistry, $learningOutcome);
    }

    #[Route(path: '/materia/resultado/{id}', name: 'organization_training_learning_outcome_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        LearningOutcome $learningOutcome
    ): Response
    {
        $subject = $learningOutcome->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $form = $this->createForm(LearningOutcomeType::class, $learningOutcome, [
            'subject' => $subject
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_learning_outcome'));
                return $this->redirectToRoute('organization_training_learning_outcome_list', [
                    'id' => $subject->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_learning_outcome'));
            }
        }

        $title = $translator->trans(
            $learningOutcome->getId() !== null ? 'title.edit' : 'title.new',
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
            $learningOutcome->getId() !== null ?
                ['fixed' => $learningOutcome->getCode()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_learning_outcome')]
        ];

        return $this->render('organization/training/learning_outcome_form.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'subject' => $subject,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/materia/{id}/resultado/{page}/', name: 'organization_training_learning_outcome_list', requirements: ['id' => '\d+', 'page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Subject $subject,
        int $page = 1
    ): Response
    {
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('l')
            ->from(LearningOutcome::class, 'l')
            ->orderBy('l.code');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('l.code LIKE :tq')
                ->orWhere('l.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('l.subject = :subject')
            ->setParameter('subject', $subject);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
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

    #[Route(path: '/materia/resultado/eliminar/{id}', name: 'organization_training_learning_outcome_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        LearningOutcomeRepository $learningOutcomeRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Subject $subject
    ): Response
    {
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_training_learning_outcome_list', ['id' => $subject->getId()]);
        }

        $learningOutcomes = $learningOutcomeRepository->findAllInListByIdAndSubject($items, $subject);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $learningOutcomeRepository->deleteFromList($learningOutcomes);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_learning_outcome'));
            } catch (\Exception) {
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
            ['fixed' => $translator->trans('title.delete', [], 'edu_learning_outcome')]
        ];

        return $this->render('organization/training/learning_outcome_delete.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'edu_learning_outcome'),
            'learning_outcomes' => $learningOutcomes
        ]);
    }

    #[Route(path: '/materia/resultado/importar/{id}', name: 'organization_training_learning_outcome_import', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function import(
        Request $request,
        LearningOutcomeRepository $learningOutcomeRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Subject $subject
    ): Response {
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $managerRegistry->getManager();

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
        } catch (\Exception) {
            $this->addFlash('error', $translator->trans('message.error', [], 'edu_learning_outcome'));
        }
        return $this->redirectToRoute('organization_training_learning_outcome_list', ['id' => $subject->getId()]);
    }

    #[Route(path: '/materia/resultado/exportar/{id}', name: 'organization_training_learning_outcome_export', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function export(
        Subject $subject
    ): Response
    {
        $training = $subject->getGrade()->getTraining();
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $data = '';

        foreach ($subject->getLearningOutcomes() as $learningOutcome) {
            $lines = explode("\n", (string) $learningOutcome->getDescription());
            foreach ($lines as &$line) {
                $line = trim($line);
            }
            $data .= $learningOutcome->getCode() . ': ' . implode(' ', $lines) . "\n";
        }

        return new Response(
            $data,
            Response::HTTP_OK,
            ['content-type' => 'text/plain']
        );
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
            preg_match('/^(.{1,10})\: (.*)/u', $item, $matches);
            if ($matches !== []) {
                $output[$matches[1]] = $matches[2];
            }
        }

        return $output;
    }
}
