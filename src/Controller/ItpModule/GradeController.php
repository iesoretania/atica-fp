<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\TrainingProgram;
use App\Form\Type\ItpModule\ProgramGradeType;
use App\Repository\Edu\GradeRepository;
use App\Repository\Edu\SubjectRepository;
use App\Repository\Edu\TrainingRepository;
use App\Repository\ItpModule\ProgramGradeLearningOutcomeRepository;
use App\Repository\ItpModule\ProgramGradeRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TrainingProgramVoter;
use Pagerfanta\Adapter\ArrayAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/plan/curso')]
class GradeController extends AbstractController
{
    #[Route(path: '/listar/{trainingProgram}/{page}', name: 'in_company_training_phase_grade_list', requirements: ['trainingProgram' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        TrainingRepository $trainingRepository,
        GradeRepository $gradeRepository,
        ProgramGradeRepository $programGradeRepository,
        TranslatorInterface $translator,
        TrainingProgram $trainingProgram,
        int $page = 1
    ): Response {
        assert($trainingProgram->getTraining() instanceof Training);
        $academicYear = $trainingProgram->getTraining()->getAcademicYear();
        assert($academicYear instanceof AcademicYear);
        $organization = $academicYear->getOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $trainingProgram);

        // Precargar grupos y enseñanzas
        $trainingRepository->findByAcademicYearWithTrainingsAndGroups($academicYear);
        $trainingRepository->findByTrainingsAndGroups($trainingProgram->getTraining());

        $grades = $gradeRepository->findByTraining($trainingProgram->getTraining());
        $programGrades = $programGradeRepository->findByTrainingProgram($trainingProgram);

        foreach ($programGrades as $programGrade) {
            if (($key = array_search($programGrade->getGrade(), $grades, true)) !== false) {
                unset($grades[$key]);
            }
        }

        $new = false;
        foreach ($grades as $grade) {
            $new = true;
            $programGrade = new ProgramGrade();
            $programGrade
                ->setTrainingProgram($trainingProgram)
                ->setGrade($grade);
            $programGradeRepository->persist($programGrade);
        }
        if ($new) {
            $programGradeRepository->flush();
        }

        $programGradeStats = $programGradeRepository->getStatsByTrainingProgram($trainingProgram);

        $adapter = new ArrayAdapter($programGradeStats);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.detail', [], 'itp_training_program')
            . ' - ' . $trainingProgram->getTraining()->__toString();

        $breadcrumb = [
            ['fixed' => $trainingProgram->getTraining()->getName()],
            ['fixed' => $translator->trans('title.detail', [], 'itp_training_program')]
        ];

        return $this->render('itp/training_program/grade_list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'domain' => 'itp_training_program',
            'training_program' => $trainingProgram
        ]);
    }
    #[Route(path: '/resultados/{programGrade}', name: 'in_company_training_phase_grade_learning_outcome_edit', requirements: ['programGrade' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                               $request,
        TranslatorInterface                   $translator,
        ProgramGradeLearningOutcomeRepository $programGradeLearningOutcomeRepository,
        SubjectRepository                     $subjectRepository,
        ProgramGrade                          $programGrade
    ): Response {
        $trainingProgram = $programGrade->getTrainingProgram();
        assert($trainingProgram instanceof TrainingProgram);
        assert($trainingProgram->getTraining() instanceof Training);
        $academicYear = $trainingProgram->getTraining()->getAcademicYear();
        assert($academicYear instanceof AcademicYear);
        $organization = $academicYear->getOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $trainingProgram);

        $subjects = $subjectRepository->findByGrade($programGrade->getGrade());

        $form = $this->createForm(ProgramGradeType::class, $programGrade, [
            'subjects' => $subjects
        ]);

        $choices = $programGradeLearningOutcomeRepository->generateByProgramGradeAndSubjects($programGrade, $programGrade->getSubjects());
        $form->get('currentProgramGradeLearningOutcomes')->setData($choices);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                foreach ($programGrade->getProgramGradeLearningOutcomes() as $programGradeLearningOutcome) {
                    $programGradeLearningOutcomeRepository->remove($programGradeLearningOutcome);
                }
                foreach ($form->get('currentProgramGradeLearningOutcomes')->getData() as $customProgramGradeLearningOutcome) {
                    switch ($customProgramGradeLearningOutcome->getSelected()) {
                        case 1:
                            $customProgramGradeLearningOutcome->getProgramGradeLearningOutcome()->setShared(true);
                            $programGradeLearningOutcomeRepository->persist($customProgramGradeLearningOutcome->getProgramGradeLearningOutcome());
                            break;
                        case 2:
                            $customProgramGradeLearningOutcome->getProgramGradeLearningOutcome()->setShared(false);
                            $programGradeLearningOutcomeRepository->persist($customProgramGradeLearningOutcome->getProgramGradeLearningOutcome());
                            break;
                        default:
                            $programGradeLearningOutcomeRepository->remove($customProgramGradeLearningOutcome->getProgramGradeLearningOutcome());
                    }
                }
                $programGradeLearningOutcomeRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_grade'));
                return $this->redirectToRoute('in_company_training_phase_grade_list', ['trainingProgram' => $trainingProgram->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_grade'));
            }
        }

        $title = $translator->trans('title.learning_outcomes', [], 'itp_grade')
            . ' - ' . $programGrade->getGrade()?->__toString();

        $breadcrumb = [
            [
                'fixed' => $trainingProgram->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $trainingProgram->getId()]
            ],
            ['fixed' => $programGrade->getGrade()?->getName()],
            ['fixed' => $translator->trans('title.learning_outcomes', [], 'itp_grade')]
        ];

        return $this->render('itp/training_program/grade_learning_outcome_form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'domain' => 'itp_grade',
            'program_grade' => $programGrade
        ]);
    }
}
