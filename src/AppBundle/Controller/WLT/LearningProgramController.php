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

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\WLT\Activity;
use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Entity\WLT\LearningProgram;
use AppBundle\Form\Model\LearningProgramImport;
use AppBundle\Form\Type\WLT\LearningProgramImportType;
use AppBundle\Form\Type\WLT\LearningProgramType;
use AppBundle\Repository\CompanyRepository;
use AppBundle\Repository\Edu\SubjectRepository;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Repository\WLT\ActivityRepository;
use AppBundle\Repository\WLT\LearningProgramRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use AppBundle\Utils\CsvImporter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/dual/programa")
 */
class LearningProgramController extends Controller
{
    /**
     * @Route("/nuevo", name="work_linked_training_learning_program_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_WORK_LINKED_TRAINING, $organization);

        $learningProgram = new LearningProgram();
        $this->getDoctrine()->getManager()->persist($learningProgram);

        return $this->indexAction($request, $userExtensionService, $translator, $learningProgram);
    }

    /**
     * @Route("/{id}", name="work_linked_training_learning_program_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        LearningProgram $learningProgram
    ) {
        if ($learningProgram->getTraining()) {
            $academicYear = $learningProgram->getTraining()->getAcademicYear();
        } else {
            $academicYear = $userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(
            OrganizationVoter::MANAGE_WORK_LINKED_TRAINING,
            $academicYear->getOrganization()
        );

        if (false === $userExtensionService->isUserLocalAdministrator() &&
            $academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createAccessDeniedException();
        }

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(LearningProgramType::class, $learningProgram);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_learning_program'));
                return $this->redirectToRoute('work_linked_training_learning_program_list', [
                    'academicYear' => $academicYear
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_learning_program'));
            }
        }

        $title = $translator->trans(
            $learningProgram->getId() ? 'title.edit' : 'title.new',
            [],
            'wlt_learning_program'
        );

        $breadcrumb = [
            $learningProgram->getId() ?
                ['fixed' => (string) $learningProgram] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_learning_program')]
        ];

        return $this->render('wlt/learning_program/form.html.twig', [
            'menu_path' => 'work_linked_training_learning_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_learning_program_list",
     *     requirements={"page" = "\d+"}, defaults={"academicYear" = null, "page" = 1}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        $page = 1,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_WORK_LINKED_TRAINING, $organization);
        if (false === $userExtensionService->isUserLocalAdministrator() &&
            $academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createAccessDeniedException();
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('cp')
            ->addSelect('SIZE(cp.activityRealizations)')
            ->from(LearningProgram::class, 'cp')
            ->join('cp.company', 'c')
            ->join('cp.training', 't')
            ->addOrderBy('c.name')
            ->addOrderBy('t.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('c.name LIKE :tq')
                ->orWhere('c.code LIKE :tq')
                ->orWhere('cp.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $translator->trans('title.list', [], 'wlt_learning_program');

        return $this->render('wlt/learning_program/list.html.twig', [
            'title' => $title . ' - ' . $academicYear,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_learning_program',
            'academic_year' => $academicYear
        ]);
    }

    /**
     * @Route("/operacion/{academicYear}", name="work_linked_training_learning_program_operation", methods={"POST"})
     */
    public function operationAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        LearningProgramRepository $learningProgramRepository,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(
            OrganizationVoter::MANAGE_WORK_LINKED_TRAINING,
            $academicYear->getOrganization()
        );

        if (false === $userExtensionService->isUserLocalAdministrator() &&
            $academicYear->getOrganization() !== $userExtensionService->getCurrentOrganization()) {
            return $this->createAccessDeniedException();
        }

        $items = $request->request->get('items', []);

        if (count($items) !== 0) {
            if ('' === $request->get('delete')) {
                return $this->deleteAction($items, $request, $translator, $learningProgramRepository, $academicYear);
            }
        }

        return $this->redirectToRoute(
            'work_linked_training_learning_program_list',
            ['academicYear' => $academicYear->getId()]
        );
    }

    private function deleteAction(
        $items,
        Request $request,
        TranslatorInterface $translator,
        LearningProgramRepository $learningProgramRepository,
        AcademicYear $academicYear
    ) {
        $em = $this->getDoctrine()->getManager();


        $learningPrograms = $learningProgramRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

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
                ['academicYear' => $academicYear->getId()]
            );
        }

        return $this->render('wlt/learning_program/delete.html.twig', [
            'menu_path' => 'work_linked_training_learning_program_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'wlt_learning_program')]],
            'title' => $translator->trans('title.delete', [], 'wlt_learning_program'),
            'items' => $learningPrograms
        ]);
    }

    /**
     * @Route("/importar", name="work_linked_training_learning_program_import", methods={"GET", "POST"})
     */
    public function importAction(
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ActivityRepository $activityRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        LearningProgramRepository $learningProgramRepository,
        CompanyRepository $companyRepository,
        SubjectRepository $subjectRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(
            OrganizationVoter::MANAGE_WORK_LINKED_TRAINING,
            $organization
        );

        $formData = new LearningProgramImport();
        $formData->setAcademicYear($organization->getCurrentAcademicYear());

        $form = $this->createForm(LearningProgramImportType::class, $formData);
        $form->handleRequest($request);

        $title = $translator->trans('title.learning_program.import', [], 'import');
        $breadcrumb = [['fixed' => $title]];

        $stats = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importFromCsv(
                $formData->getFile()->getPathname(),
                $formData->getTraining(),
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
        return $this->render('wlt/learning_program/import_form.html.twig', [
            'menu_path' => 'work_linked_training_learning_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'stats' => $stats
        ]);
    }

    private function importFromCsv(
        $file,
        Training $training,
        ActivityRepository $activityRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        LearningProgramRepository $learningProgramRepository,
        CompanyRepository $companyRepository,
        SubjectRepository $subjectRepository,
        EntityManagerInterface $entityManager
    ) {
        $newActivityCount = 0;
        $oldActivityCount = 0;

        $newCompanyCount = 0;
        $oldCompanyCount = 0;

        $unknownSubjects = [];
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

                    if ($companiesParsed === false && '' === $lineData[0]) {
                        for ($i = count($lineData) - 1; $i > 1; $i--) {
                            if ($lineData[$i]) {
                                $company = $companyRepository->findOneBy(['code' => $lineData[$i]]);
                                if ($company) {
                                    $learningProgram = $learningProgramRepository->findOneBy(
                                        [
                                            'company' => $company,
                                            'training' => $training
                                        ]
                                    );
                                    if (null === $learningProgram) {
                                        $learningProgram = new LearningProgram();
                                        $learningProgram
                                            ->setTraining($training)
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
                            $activity = $activityRepository->findOneByTrainingAndCode($training, $lineData[0]);
                            if (null === $activity) {
                                // obtener código de la asignatura y buscarla
                                preg_match('/^([A-Za-z]*)/', $lineData[0], $output);
                                $lastCode = $output[0];
                                $subject = $subjectRepository->findOneByAcademicYearAndCode(
                                    $training->getAcademicYear(),
                                    $lastCode
                                );
                                // si no hay asignatura, ignorar las actividades
                                if (null !== $subject) {
                                    $activity = new Activity();
                                    $activity
                                        ->setSubject($subject)
                                        ->setDescription($lineData[1])
                                        ->setCode($lineData[0]);
                                    $entityManager->persist($activity);
                                } else {
                                    $unknownSubjects[$lastCode] = $lastCode;
                                }
                            } else {
                                $lastCode = $activity->getCode();
                            }
                        } else {
                            if ($activity && strpos($lineData[0], $lastCode) === 0 &&
                                (strlen($lineData[2]) === 2 || strlen($lineData[2]) === 1)) {
                                // Procesar concreción
                                $activityRealization = $activityRealizationRepository->findOneByTrainingAndCode(
                                    $training,
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
                                    $activityRealizations = $learningProgram->getActivityRealizations();
                                    if (strpos($lineData[$n + 2], 'S') === 0) {
                                        if (false === $activityRealizations->contains($activityRealization)) {
                                            $activityRealizations->add($activityRealization);
                                        }
                                    }
                                    if (strpos($lineData[$n + 2], 'N') === 0) {
                                        if (true === $activityRealizations->contains($activityRealization)) {
                                            $activityRealizations->removeElement($activityRealization);
                                        }
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
            ],
            'subject' => [
                'unknown_items' => $unknownSubjects
            ]
        ];
    }
}
