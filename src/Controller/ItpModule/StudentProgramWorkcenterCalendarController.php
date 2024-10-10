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

use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Security\ItpModule\TrainingProgramVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/plan/curso/grupo/estudiante/estancia/calendario')]
class StudentProgramWorkcenterCalendarController extends AbstractController
{
    #[Route(path: '/{studentProgramWorkcenter}', name: 'in_company_training_phase_student_program_workcenter_calendar_list', requirements: ['studentProgramWorkcenter' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramWorkcenter $studentProgramWorkcenter
    ): Response
    {
        assert($studentProgramWorkcenter instanceof StudentProgramWorkcenter);
        $studentProgram = $studentProgramWorkcenter->getStudentProgram();
        assert($studentProgram instanceof StudentProgram);
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $title = $translator->trans('title.calendar', [], 'itp_student_program_workcenter');
        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGroup->getProgramGrade()->getId()]
            ],
            [
                'fixed' => $studentProgram->getProgramGroup()->getGroup()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_list',
                'routeParams' => ['programGroup' => $programGroup->getId()]
            ],
            [
                'fixed' => $studentProgram->getStudentEnrollment()->getPerson()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_workcenter_list',
                'routeParams' => ['studentProgram' => $studentProgram->getId()]
            ],
            [
                'fixed' => $studentProgramWorkcenter->getWorkcenter()->__toString()
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/workcenter/calendar_list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'domain' => 'itp_student_program_workcenter',
            'calendar' => [],
            'read_only' => false,
            'student_program_workcenter' => $studentProgramWorkcenter
        ]);
    }
}
