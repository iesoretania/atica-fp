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
use App\Form\Type\ItpModule\ProgramGradesType;
use App\Repository\Edu\GradeRepository;
use App\Repository\Edu\TrainingRepository;
use App\Repository\ItpModule\ProgramGradeRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TrainingProgramVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/programa/detalle')]
class TrainingProgramDetailController extends AbstractController
{
    #[Route(path: '/listar/{trainingProgram}', name: 'in_company_training_phase_training_program_detail', requirements: ['trainingProgram' => '\d+'], methods: ['GET', 'POST'])]
    public function list(
        Request $request,
        TrainingRepository $trainingRepository,
        GradeRepository $gradeRepository,
        ProgramGradeRepository $programGradeRepository,
        TranslatorInterface $translator,
        TrainingProgram $trainingProgram
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

        foreach ($grades as $grade) {
            $programGrade = new ProgramGrade();
            $programGrade
                ->setTrainingProgram($trainingProgram)
                ->setGrade($grade);
            $programGradeRepository->persist($programGrade);
            $programGrades[] = $programGrade;

            // ordenar colección
            uasort(
                $programGrades,
                static fn(ProgramGrade $a, ProgramGrade $b) => $a->getGrade()->getName() <=> $b->getGrade()->getName()
            );
        }

        $programGradesData = [
            'programGrades' => $programGrades
        ];

        $formProgramGrades = $this->createForm(ProgramGradesType::class, $programGradesData);
        $formProgramGrades->handleRequest($request);

        if ($formProgramGrades->isSubmitted() && $formProgramGrades->isValid()) {
            try {
                $programGradeRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_training_program'));
                return $this->redirectToRoute('in_company_training_phase_training_program_list',
                    ['academicYear' => $academicYear->getId()]
                );
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_training_program'));
            }
        }

        $title = $translator->trans('title.detail', [], 'itp_training_program');

        $breadcrumb = [
            ['fixed' => $trainingProgram->getTraining()->getName()],
            ['fixed' => $translator->trans('title.detail', [], 'itp_training_program')]
        ];

        return $this->render('itp/training_program/detail.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'form_program_grades' => $formProgramGrades->createView(),
            'title' => $title,
            'domain' => 'itp_training_program',
            'training_program' => $trainingProgram
        ]);
    }
}
