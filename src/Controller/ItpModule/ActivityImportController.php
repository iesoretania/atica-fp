<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Controller\ItpModule;

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use App\Form\Model\ItpModule\ActivityImport;
use App\Form\Type\ItpModule\ActivityImportType;
use App\Repository\Edu\CriterionRepository;
use App\Repository\Edu\LearningOutcomeRepository;
use App\Repository\Edu\SubjectRepository;
use App\Repository\ItpModule\ActivityRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Utils\CsvImporter;
use Doctrine\ORM\Query\QueryException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/plan/actividad')]
class ActivityImportController extends AbstractController
{
    static private $columns = [
        ['column' => 'Actividad', 'mandatory' => true],
        ['column' => 'Módulo', 'mandatory' => true],
        ['column' => 'Resultado de aprendizaje asociado', 'mandatory' => true],
        ['column' => 'Criterio de evaluación', 'mandatory' => true]
    ];

    #[Route(path: '/importar/{programGrade}', name: 'in_company_training_phase_activity_import_form', requirements: ['programGrade' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        TranslatorInterface $translator,
        ActivityRepository $activityRepository,
        CriterionRepository $criterionRepository,
        LearningOutcomeRepository $learningOutcomeRepository,
        SubjectRepository $subjectRepository,
        ProgramGrade $programGrade,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $formData = new ActivityImport();
        $form = $this->createForm(ActivityImportType::class, $formData);
        $form->handleRequest($request);

        $stats = null;
        $breadcrumb = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $stats = $this->importFromCsv(
                $formData->getFile()?->getPathname(),
                $programGrade,
                $activityRepository,
                $criterionRepository,
                $learningOutcomeRepository,
                $subjectRepository
            );

            if (!isset($stats['error'])) {
                $this->addFlash('success', $translator->trans('message.import_ok', [], 'itp_activity'));
                $breadcrumb[] = ['fixed' => $translator->trans('title.import_result', [], 'itp_activity')];
            } else {
                $this->addFlash('error', $translator->trans('message.import_error' . $stats['error'], [], 'itp_activity'));
            }
        }


        $title = $translator->trans('title.import', [], 'itp_activity')
            . ' - ' . $programGrade->getGrade()->__toString();

        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_activity_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            ['fixed' => $translator->trans('title.import', [], 'itp_activity')]
        ];

        return $this->render('itp/training_program/activity/import_form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'form' => $form->createView(),
            'columns' => self::$columns,
            'stats' => $stats
        ]);
    }

    private function importFromCsv(
        string $file,
        ProgramGrade $programGrade,
        ActivityRepository $activityRepository,
        CriterionRepository $criterionRepository,
        LearningOutcomeRepository $learningOutcomeRepository,
        SubjectRepository $subjectRepository,
        array $options = []
    ): array {
        $newCount = 0;
        $oldCount = 0;
        $unknownCount = 0;

        // Precargar módulos profesionales
        $subjects = $subjectRepository->findByGrade($programGrade->getGrade());
        $subjectCollection = [];
        foreach ($subjects as $subject) {
            $subjectCollection[$subject->getName()]['subject'] = $subject;
        }

        // Precargar resultados de aprendizaje
        $learningOutcomes = $learningOutcomeRepository->findByGrade($programGrade->getGrade());
        foreach ($learningOutcomes as $learningOutcome) {
            assert($learningOutcome instanceof LearningOutcome);
            $subjectName = $learningOutcome->getSubject()->getName();
            $learningOutcomeCode = $learningOutcome->getCode();
            if (str_starts_with($learningOutcomeCode, 'RA')) {
                $learningOutcomeCode = substr($learningOutcomeCode, 2);
            }
            $subjectCollection[$subjectName]['learning_outcomes'][$learningOutcomeCode]['learning_outcome'] = $learningOutcome;
        }

        // Precargar criterios de evaluación
        $criteria = $criterionRepository->findByGrade($programGrade->getGrade());
        foreach ($criteria as $criterion) {
            assert($criterion instanceof Criterion);
            $learningOutcome = $criterion->getLearningOutcome();
            $learningOutcomeCode = $learningOutcome->getCode();
            if (str_starts_with($learningOutcomeCode, 'RA')) {
                $learningOutcomeCode = substr($learningOutcomeCode, 2);
            }
            $subjectName = $criterion->getLearningOutcome()->getSubject()->getName();
            $subjectCollection[$subjectName]['learning_outcomes'][$learningOutcomeCode]['criteria'][$criterion->getCode()] = $criterion;
        }
        // Precargar actividades existentes
        $activities = $activityRepository->findByProgramGrade($programGrade);
        $activityCollection = [];
        foreach ($activities as $activity) {
            $activityCollection[$activity->getCode()] = $activity;
        }
        $oldCount = count($activities);

        $importer = new CsvImporter($file, true);

        try {
            while ($data = $importer->get(100)) {
                foreach ($data as $subjectData) {
                    foreach (self::$columns as $columnData) {
                        if ($columnData['mandatory'] && !isset($subjectData[$columnData['column']])) {
                            return ['error' => '_missing_columns'];
                        }
                    }

                    // Actividad
                    $activityName = trim((string) $subjectData['Actividad']);
                    $matchResult = preg_match('/(AF\d+)\. (.*)/', $activityName, $matches);
                    if ($matchResult === 0) {
                        return ['error' => '_wrong_activity_format'];
                    }
                    $activityCode = $matches[1];
                    $activityDescription = $matches[2];

                    if (isset($activityCollection[$activityCode])) {
                        $activity = $activityCollection[$activityCode];
                    } else {
                        $activity = new Activity();
                        $activity
                            ->setProgramGrade($programGrade)
                            ->setCode($activityCode);
                        $activityRepository->persist($activity);
                        $newCount++;
                        $activityCollection[$activityCode] = $activity;
                    }
                    $activity->setName($activityDescription);

                    // Módulo profesional
                    $subjectDescription = trim((string) $subjectData['Módulo']);
                    $matchResult = preg_match('/.*\(.*\) \/ (.*)$/', $subjectDescription, $matches);
                    if ($matchResult === 0) {
                        return ['error' => '_wrong_subject_format'];
                    }
                    $subjectName = $matches[1];
                    if (!isset($subjectCollection[$subjectName])) {
                        $unknownCount++;
                        continue;
                    }
                    $learningOutcomes = $subjectCollection[$subjectName]['learning_outcomes'];

                    // Resultado de aprendizaje asociado
                    $learningOutcomeName = trim((string) $subjectData['Resultado de aprendizaje asociado']);
                    $matchResult = preg_match('/R.A.(\d+)\. (.*)/', $learningOutcomeName, $matches);
                    if ($matchResult === 0) {
                        return ['error' => '_wrong_learning_outcome_format'];
                    }
                    $learningOutcomeCode = $matches[1];
                    if (!isset($learningOutcomes[$learningOutcomeCode])) {
                        $unknownCount++;
                        continue;
                    }
                    $criteria = $learningOutcomes[$learningOutcomeCode]['criteria'];

                    // Criterio de evaluación
                    $criterionName = trim((string) $subjectData['Criterio de evaluación']);
                    $matchResult = preg_match('/([a-z]+)\. (.*)/', $criterionName, $matches);
                    if ($matchResult === 0) {
                        return ['error' => '_wrong_criterion_format'];
                    }
                    $criterionCode = $matches[1];
                    if (!isset($criteria[$criterionCode])) {
                        $unknownCount++;
                        continue;
                    }
                    $criterion = $criteria[$criterionCode];
                    $activity->addCriterion($criterion);
                }
            }
            $activityRepository->flush();
        } catch (QueryException) {
            return ['error' => '_query'];
        } catch (Exception) {
            return ['error' => ''];
        }

        return [
            'new_items' => $newCount,
            'old_items' => $oldCount,
            'unknown_items' => $unknownCount
        ];
    }
}
