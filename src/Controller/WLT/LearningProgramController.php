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

namespace App\Controller\WLT;

use App\Entity\Company;
use App\Entity\WLT\Activity;
use App\Entity\WLT\ActivityRealization;
use App\Entity\WLT\LearningProgram;
use App\Entity\WLT\Project;
use App\Form\Model\LearningProgramImport;
use App\Form\Type\WLT\LearningProgramImportType;
use App\Form\Type\WLT\LearningProgramType;
use App\Repository\CompanyRepository;
use App\Repository\WLT\ActivityRealizationRepository;
use App\Repository\WLT\ActivityRepository;
use App\Repository\WLT\LearningProgramRepository;
use App\Repository\WLT\WLTSubjectRepository;
use App\Security\WLT\ProjectVoter;
use App\Utils\CsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/dual/programa")
 */
class LearningProgramController extends AbstractController
{
    /**
     * @Route("/nuevo/{project}", name="work_linked_training_learning_program_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $learningProgram = new LearningProgram();
        $learningProgram->setProject($project);
        $this->getDoctrine()->getManager()->persist($learningProgram);

        return $this->indexAction($request, $translator, $learningProgram);
    }

    /**
     * @Route("/{id}", name="work_linked_training_learning_program_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        LearningProgram $learningProgram
    ) {
        $project = $learningProgram->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $em = $this->getDoctrine()->getManager();

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
            } catch (\Exception $e) {
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

    /**
     * @Route("/{project}/listar/{page}", name="work_linked_training_learning_program_list",
     *     requirements={"page" = "\d+"}, methods={"GET"})
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
        } catch (OutOfRangeCurrentPageException $e) {
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

    /**
     * @Route("/operacion/{project}", name="work_linked_training_learning_program_operation", methods={"POST"})
     */
    public function operationAction(
        Request $request,
        TranslatorInterface $translator,
        LearningProgramRepository $learningProgramRepository,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $items = $request->request->get('items', []);

        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) !== 0 && '' === $request->get('delete')) {
            return $this->deleteAction($items, $request, $translator, $learningProgramRepository, $project);
        }

        return $this->redirectToRoute(
            'work_linked_training_learning_program_list',
            ['project' => $project->getId()]
        );
    }

    private function deleteAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        LearningProgramRepository $learningProgramRepository,
        Project $project
    ) {
        $em = $this->getDoctrine()->getManager();

        $learningPrograms = $learningProgramRepository->findAllInListByIdAndProject($items, $project);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $learningProgramRepository->deleteFromList($learningPrograms);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_learning_program'));
            } catch (\Exception $e) {
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

    /**
     * @Route("/importar/{project}", name="work_linked_training_learning_program_import", methods={"GET", "POST"})
     */
    public function importAction(
        TranslatorInterface $translator,
        ActivityRepository $activityRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        LearningProgramRepository $learningProgramRepository,
        CompanyRepository $companyRepository,
        WLTSubjectRepository $subjectRepository,
        EntityManagerInterface $entityManager,
        Project $project,
        Request $request
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $formData = new LearningProgramImport();
        $formData->setProject($project);

        $form = $this->createForm(LearningProgramImportType::class, $formData, [
            'projects' => [$project]
        ]);

        $form->handleRequest($request);

        $title = $translator->trans('title.learning_program.import', [], 'import');
        $breadcrumb = [
            [
                'fixed' => $project->getName(),
                'routeName' => 'work_linked_training_learning_program_list',
                'routeParams' => ['project' => $project->getId()]
            ],
            ['fixed' => $title]
        ];

        $stats = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importFromCsv(
                $formData->getFile()->getPathname(),
                $formData->getProject(),
                $activityRepository,
                $activityRealizationRepository,
                $learningProgramRepository,
                $companyRepository,
                $subjectRepository,
                $entityManager
            );

            if (null !== $stats) {
                $this->addFlash('success', $translator->trans('message.import_ok', [], 'import'));
                $breadcrumb[] = ['fixed' => $translator->trans('title.import_result', [], 'import')];
            } else {
                $this->addFlash('error', $translator->trans('message.import_error', [], 'import'));
            }
        }
        return $this->render('wlt/project/company/import_form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'stats' => $stats
        ]);
    }

    private function importFromCsv(
        $file,
        Project $project,
        ActivityRepository $activityRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        LearningProgramRepository $learningProgramRepository,
        CompanyRepository $companyRepository,
        WLTSubjectRepository $subjectRepository,
        EntityManagerInterface $entityManager
    ) {
        $newActivityCount = 0;
        $oldActivityCount = 0;

        $newCompanyCount = 0;
        $oldCompanyCount = 0;

        $unknownCompanies = [];
        $unknownActivities = [];

        $importer = new CsvImporter($file, false);

        $companiesParsed = false;
        $learningPrograms = [];

        $lastCode = '!';
        $activity = null;

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $lineData) {
                    if ('' === $lineData[1]) {
                        continue;
                    }

                    if (!$companiesParsed && '' === $lineData[0]) {
                        $count = is_array($lineData) || $lineData instanceof \Countable ? count($lineData) : 0;
                        for ($i = 3; $i <= $count - 1; $i++) {
                            if ($lineData[$i]) {
                                /** @var Company $company */
                                $company = $companyRepository->findOneBy(['code' => $lineData[$i]]);
                                if ($company) {
                                    $learningProgram = $learningProgramRepository->findOneBy(
                                        [
                                            'company' => $company,
                                            'project' => $project
                                        ]
                                    );
                                    if (null === $learningProgram) {
                                        $learningProgram = new LearningProgram();
                                        $learningProgram
                                            ->setProject($project)
                                            ->setCompany($company);
                                        $entityManager->persist($learningProgram);
                                        $newCompanyCount++;
                                    } else {
                                        $oldCompanyCount++;
                                    }
                                    $learningPrograms[$i] = $learningProgram;
                                } else {
                                    $unknownCompanies[] = $lineData[$i];
                                }
                            }
                        }
                        $companiesParsed = true;
                        continue;
                    }
                    if ($companiesParsed) {
                        // Nueva actividad
                        if ($lineData[0] !== '' && $lineData[0] === $lineData[2]) {
                            $activity = $activityRepository->findOneByProjectAndCode($project, $lineData[0]);
                            if (null === $activity) {
                                // obtener código de la asignatura y buscarla
                                preg_match('/^([A-Za-z]*)/', $lineData[0], $output);
                                $lastCode = $output[0];
                                $activity = new Activity();
                                $activity
                                    ->setProject($project)
                                    ->setDescription($lineData[1])
                                    ->setCode($lineData[0]);
                                $entityManager->persist($activity);
                            } else {
                                $lastCode = $activity->getCode();
                            }
                        } elseif ($activity && strpos($lineData[0], $lastCode) === 0 &&
                            (strlen($lineData[2]) === 2 || strlen($lineData[2]) === 1)) {
                            // Procesar concreción
                            $activityRealization = $activityRealizationRepository->findOneByProjectAndCode(
                                $project,
                                $lineData[0]
                            );
                            if (null === $activityRealization) {
                                $activityRealization = new ActivityRealization();
                                $activityRealization
                                    ->setActivity($activity)
                                    ->setCode($lineData[0])
                                    ->setDescription($lineData[1]);
                                $entityManager->persist($activityRealization);
                                $newActivityCount++;
                            } else {
                                $oldActivityCount++;
                            }
                            /** @var LearningProgram $learningProgram */
                            foreach ($learningPrograms as $n => $learningProgram) {
                                if (isset($lineData[$n])) {
                                    $activityRealizations = $learningProgram->getActivityRealizations();
                                    if ((strpos($lineData[$n], 'S') === 0) &&
                                        !$activityRealizations->contains($activityRealization)
                                    ) {
                                        $activityRealizations->add($activityRealization);
                                    } elseif ((strpos($lineData[$n], 'N') === 0) &&
                                        $activityRealizations->contains($activityRealization)
                                    ) {
                                        $activityRealizations->removeElement($activityRealization);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $entityManager->flush();
        } catch (\Exception $e) {
            return null;
        }


        return [
            'activity' => [
                'new_items' => $newActivityCount,
                'old_items' => $oldActivityCount,
                'unknown_items' => $unknownActivities
            ],
            'company' => [
                'new_items' => $newCompanyCount,
                'old_items' => $oldCompanyCount,
                'unknown_items' => $unknownCompanies
            ]
        ];
    }
}
