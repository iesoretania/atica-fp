<?php


namespace AppBundle\Controller\Organization;

use AppBundle\Entity\Edu\Criterion;
use AppBundle\Entity\Edu\LearningOutcome;
use AppBundle\Form\Type\Edu\CriterionType;
use AppBundle\Repository\Edu\CriterionRepository;
use AppBundle\Security\Edu\TrainingVoter;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/centro/ensenanza/materia/resultado")
 */
class CriterionController extends Controller
{
    /**
     * @Route("/{id}/criterio/nuevo", name="organization_training_criterion_new",
     *     methods={"GET", "POST"})
     **/
    public function newCriterionAction(
        Request $request,
        TranslatorInterface $translator,
        LearningOutcome $learningOutcome
    ) {
        $subject = $learningOutcome->getSubject();
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        $criterion = new Criterion();
        $criterion
            ->setLearningOutcome($learningOutcome);

        $this->getDoctrine()->getManager()->persist($criterion);

        return $this->criterionFormAction($request, $translator, $criterion);
    }

    /**
     * @Route("/criterio/{id}", name="organization_training_criterion_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function criterionFormAction(
        Request $request,
        TranslatorInterface $translator,
        Criterion $criterion
    ) {
        $subject = $criterion->getLearningOutcome()->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $form = $this->createForm(CriterionType::class, $criterion, [
            'learning_outcome' => $criterion->getLearningOutcome()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_criterion'));
                return $this->redirectToRoute('organization_training_criterion_list', [
                    'id' => $criterion->getLearningOutcome()->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_criterion'));
            }
        }

        $title = $translator->trans(
            $criterion->getId() ? 'title.edit' : 'title.new',
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
            $criterion->getId() ?
                ['fixed' => $criterion->getCode()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'edu_criterion')]
        ];

        return $this->render('organization/training/criterion_form.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'subject' => $subject,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/criterio/{page}/", name="organization_training_criterion_list",
     *     requirements={"id" = "\d+", "page" = "\d+"}, defaults={"page" = 1}, methods={"GET"})
     */
    public function criterionListAction(
        Request $request,
        TranslatorInterface $translator,
        LearningOutcome $learningOutcome,
        $page = 1
    ) {
        $subject = $learningOutcome->getSubject();
        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $subject->getGrade()->getTraining());

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('c')
            ->from(Criterion::class, 'c')
            ->orderBy('c.code');

        $q = $request->get('q', null);
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

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
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

    /**
     * @Route("/eliminar/{id}", name="organization_training_criterion_delete",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        CriterionRepository $criterionRepository,
        TranslatorInterface $translator,
        LearningOutcome $learningOutcome
    ) {
        $subject = $learningOutcome->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('organization_training_criterion_list', ['id' => $learningOutcome->getId()]);
        }

        $criteria = $criterionRepository->findAllInListByIdAndLearningOutcome($items, $learningOutcome);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $criterionRepository->deleteFromList($criteria);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_criterion'));
            } catch (\Exception $e) {
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
            ['fixed' => $this->get('translator')->trans('title.delete', [], 'edu_criterion')]
        ];

        return $this->render('organization/training/criterion_delete.html.twig', [
            'menu_path' => 'organization_subject_list',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'edu_learning_outcome'),
            'criteria' => $criteria
        ]);
    }

    /**
     * @Route("/criterio/importar/{id}", name="organization_training_criterion_import",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function importAction(
        Request $request,
        CriterionRepository $criterionRepository,
        TranslatorInterface $translator,
        LearningOutcome $learningOutcome
    ) {
        $subject = $learningOutcome->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $em = $this->getDoctrine()->getManager();

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
        } catch (\Exception $e) {
            $this->addFlash('error', $translator->trans('message.error', [], 'edu_criterion'));
        }
        return $this->redirectToRoute('organization_training_criterion_list', ['id' => $learningOutcome->getId()]);
    }


    /**
     * @Route("/criterio/exportar/{id}", name="organization_training_criterion_export",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function exportAction(
        LearningOutcome $learningOutcome
    ) {
        $subject = $learningOutcome->getSubject();
        $training = $subject->getGrade()->getTraining();

        $this->denyAccessUnlessGranted(TrainingVoter::MANAGE, $training);

        $data = '';

        foreach ($learningOutcome->getCriteria() as $criterion) {
            $lines = explode("\n", $criterion->getName());
            foreach ($lines as &$line) {
                $line = trim($line);
            }
            $data .= $criterion->getCode() . ') ' .  implode(' ', $lines) . "\n";
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
            preg_match('/^(.{1,10})\) (.*)/u', $item, $matches);
            if ($matches) {
                $output[$matches[1]] = $matches[2];
            }
        }

        return $output;
    }
}
