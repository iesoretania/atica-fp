<?php


namespace App\Controller\Organization;

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Form\Type\Edu\CriterionType;
use App\Repository\Edu\CriterionRepository;
use App\Security\Edu\TrainingVoter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/centro/ensenanza/materia/resultado')]
class CriterionController extends AbstractController
{
    #[Route(path: '/{id}/criterio/nuevo', name: 'organization_training_criterion_new', methods: ['GET', 'POST'])]
    public function newCriterion(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        LearningOutcome $learningOutcome
    ): Response
    {
        $subject = $learningOutcome->getSubject();
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        $criterion = new Criterion();
        $criterion
            ->setLearningOutcome($learningOutcome);

        $managerRegistry->getManager()->persist($criterion);

        return $this->criterionForm($request, $translator, $managerRegistry, $criterion);
    }

    #[Route(path: '/criterio/{id}', name: 'organization_training_criterion_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function criterionForm(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Criterion $criterion
    ): Response
    {
        $subject = $criterion->getLearningOutcome()->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $form = $this->createForm(CriterionType::class, $criterion, [
            'learning_outcome' => $criterion->getLearningOutcome()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_criterion'));
                return $this->redirectToRoute('organization_training_criterion_list', [
                    'id' => $criterion->getLearningOutcome()->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_criterion'));
            }
        }

        $title = $translator->trans(
            $criterion->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_criterion'
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
            [
                'fixed' => $criterion->getLearningOutcome()->getCode(),
                'routeName' => 'organization_training_criterion_list',
                'routeParams' => ['id' => $criterion->getLearningOutcome()->getId()]
            ],
            $criterion->getId() !== null ?
                ['fixed' => $criterion->getCode()] :
                ['fixed' => $translator->trans('title.new', [], 'edu_criterion')]
        ];

        return $this->render('organization/training/criterion_form.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'subject' => $subject,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/criterio/{page}/', name: 'organization_training_criterion_list', requirements: ['id' => '\d+', 'page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function criterionList(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        LearningOutcome $learningOutcome,
        int $page = 1
    ): Response
    {
        $subject = $learningOutcome->getSubject();
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('c')
            ->from(Criterion::class, 'c')
            ->orderBy('c.code');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('c.code LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('c.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('c.learningOutcome = :learning_outcome')
            ->setParameter('learning_outcome', $learningOutcome);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $learningOutcome->getCode() . ' - ' . $translator->trans('title.list', [], 'edu_criterion');

        $breadcrumb = [
            [
                'fixed' => $subject->getGrade()->getTraining()->getName(),
                'routeName' => 'organization_subject_list',
                'routeParams' => []
            ],
            [
                'fixed' => $subject->getName(),
                'routeName' => 'organization_training_learning_outcome_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            [
                'fixed' => $learningOutcome->getCode()
            ],
            ['fixed' => $translator->trans('title.list', [], 'edu_criterion')]
        ];

        return $this->render('organization/training/criterion_list.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'learning_outcome' => $learningOutcome,
            'domain' => 'edu_criterion'
        ]);
    }

    #[Route(path: '/criterio/eliminar/{id}', name: 'organization_training_criterion_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        CriterionRepository $criterionRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        LearningOutcome $learningOutcome
    ): Response
    {
        $subject = $learningOutcome->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_training_criterion_list', ['id' => $learningOutcome->getId()]);
        }

        $criteria = $criterionRepository->findAllInListByIdAndLearningOutcome($items, $learningOutcome);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $criterionRepository->deleteFromList($criteria);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_criterion'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_criterion'));
            }
            return $this->redirectToRoute('organization_training_criterion_list', ['id' => $learningOutcome->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $subject->getGrade()->getTraining()->getName(),
                'routeName' => 'organization_subject_list',
                'routeParams' => []
            ],
            [
                'fixed' => $subject->getName(),
                'routeName' => 'organization_training_learning_outcome_list',
                'routeParams' => ['id' => $subject->getId()]
            ],
            [
                'fixed' => $learningOutcome->getCode(),
                'routeName' => 'organization_training_criterion_list',
                'routeParams' => ['id' => $learningOutcome->getId()]
            ],
            ['fixed' => $translator->trans('title.delete', [], 'edu_criterion')]
        ];

        return $this->render('organization/training/criterion_delete.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'edu_learning_outcome'),
            'criteria' => $criteria
        ]);
    }

    #[Route(path: '/criterio/importar/{id}', name: 'organization_training_criterion_import', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function import(
        Request $request,
        CriterionRepository $criterionRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        LearningOutcome $learningOutcome
    ): Response {
        $subject = $learningOutcome->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $managerRegistry->getManager();

        $lines = trim($request->request->get('data', []));
        if ($lines === '') {
            return $this->redirectToRoute('organization_training_learning_outcome_list', ['id' => $subject->getId()]);
        }

        $items = $this->parseImport($lines);
        foreach ($items as $code => $item) {
            $criterion = $criterionRepository->findOneByCodeAndLearningOutcome($code, $learningOutcome);
            if (null === $criterion) {
                $criterion = new Criterion();
                $em->persist($criterion);
            }
            $criterion
                ->setLearningOutcome($learningOutcome)
                ->setCode($code)
                ->setName($item);
        }
        try {
            $em->flush();
            $this->addFlash('success', $translator->trans('message.saved', [], 'edu_criterion'));
        } catch (\Exception) {
            $this->addFlash('error', $translator->trans('message.error', [], 'edu_criterion'));
        }
        return $this->redirectToRoute('organization_training_criterion_list', ['id' => $learningOutcome->getId()]);
    }


    #[Route(path: '/criterio/exportar/{id}', name: 'organization_training_criterion_export', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function export(
        LearningOutcome $learningOutcome
    ): Response
    {
        $subject = $learningOutcome->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $data = '';

        foreach ($learningOutcome->getCriteria() as $criterion) {
            $lines = explode("\n", (string) $criterion->getName());
            foreach ($lines as &$line) {
                $line = trim($line);
            }
            $data .= $criterion->getCode() . ') ' .  implode(' ', $lines) . "\n";
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
            preg_match('/^(.{1,10})\) (.*)/u', $item, $matches);
            if ($matches !== []) {
                $output[$matches[1]] = $matches[2];
            }
        }

        return $output;
    }
}
