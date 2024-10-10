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
use App\Entity\Edu\Grade;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\Person;
use App\Form\Type\ItpModule\StudentProgramType;
use App\Repository\ItpModule\StudentProgramRepository;
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

#[Route(path: '/formacion/plan/curso/grupo/estudiante')]
class StudentProgramController extends AbstractController
{
    #[Route(path: '/listar/{programGroup}/{page}', name: 'in_company_training_phase_student_program_list', requirements: ['programGroup' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                  $request,
        TranslatorInterface      $translator,
        StudentProgramRepository $studentProgramRepository,
        ProgramGroup             $programGroup,
        int                      $page = 1
    ): Response
    {
        assert($programGroup instanceof ProgramGroup);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGroup->getProgramGrade()->getTrainingProgram());

        $q = $request->get('q');

        /** @var Person $person */
        $person = $this->getUser();

        $studentPrograms = $studentProgramRepository->findOrCreateAllByProgramGroup(
            $programGroup,
            $q
        );

        $adapter = new ArrayAdapter($studentPrograms);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'itp_student_program')
            . ' - ' . $programGroup->getGroup()->__toString();

        $breadcrumb = [
            [
                'fixed' => $programGroup->getProgramGrade()->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGroup->getProgramGrade()->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGroup->getProgramGrade()->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGroup->getProgramGrade()->getId()]
            ],
            ['fixed' => $programGroup->getGroup()->__toString()],
            ['fixed' => $translator->trans('title.list', [], 'itp_student_program')]
        ];

        return $this->render('itp/training_program/student_program/list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_student_program',
            'program_group' => $programGroup
        ]);
    }

    #[Route(path: '/nueva/{programGroup}', name: 'in_company_training_phase_student_program_new', requirements: ['programGroup' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request                  $request,
        TranslatorInterface      $translator,
        StudentProgramRepository $studentProgramRepository,
        ProgramGroup             $programGroup
    ): Response
    {
        $programGrade = $programGroup->getProgramGrade();
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $studentProgram = new StudentProgram();
        $studentProgram
            ->setProgramGroup($programGroup);

        $studentProgramRepository->persist($studentProgram);

        return $this->edit($request, $translator, $studentProgramRepository, $studentProgram);
    }

    #[Route(path: '/{studentProgram}', name: 'in_company_training_phase_student_program_edit', requirements: ['studentProgram' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                  $request,
        TranslatorInterface      $translator,
        StudentProgramRepository $studentProgramRepository,
        StudentProgram           $studentProgram
    ): Response {
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $form = $this->createForm(StudentProgramType::class, $studentProgram);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $studentProgramRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_student_program'));
                return $this->redirectToRoute('in_company_training_phase_student_program_list', ['programGroup' => $programGroup->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_student_program'));
            }
        }

        $title = ($studentProgram->getId() === null ? $translator->trans(
            'title.new',
            [],
            'itp_student_program'
        ) : $studentProgram->getStudentEnrollment()->getPerson()->__toString()) .  ' - ' . $programGroup->getGroup()->__toString();

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
            ['fixed' => $studentProgram->getId()
                ? ($studentProgram->getStudentEnrollment()->getPerson()->__toString())
                : $translator->trans('title.new', [], 'itp_student_program')
            ]
        ];

        return $this->render('itp/training_program/student_program/form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'student_program' => $studentProgram,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/limpiar/{programGroup}', name: 'in_company_training_phase_student_program_operation', requirements: ['programGroup' => '\d+'], methods: ['POST'])]
    public function operation(
        Request                  $request,
        TranslatorInterface      $translator,
        StudentProgramRepository $studentProgramRepository,
        ProgramGroup             $programGroup
    ): Response {
        $grade = $programGroup->getProgramGrade()->getGrade();
        assert($grade instanceof Grade);
        assert($grade->getTraining() instanceof Training);
        $academicYear = $grade->getTraining()->getAcademicYear();
        assert($academicYear instanceof AcademicYear);
        $organization = $academicYear->getOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGroup->getProgramGrade()->getTrainingProgram());

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute('in_company_training_phase_student_program_list', ['programGroup' => $programGroup->getId()]);
        }
        $selectedItems = $studentProgramRepository->findAllInListByIdAndProgramGroup($items, $programGroup);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $studentProgramRepository->deleteFromList($selectedItems);
                $studentProgramRepository->flush();
                $this->addFlash('success', $translator->trans('message.cleaned', [], 'itp_student_program'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.clean_error', [], 'itp_student_program'));
            }
            return $this->redirectToRoute('in_company_training_phase_student_program_list', ['programGroup' => $programGroup->getId()]);
        }

        $title = $translator->trans('title.clean', [], 'itp_student_program');

        $breadcrumb = [
            [
                'fixed' => $programGroup->getProgramGrade()->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGroup->getProgramGrade()->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGroup->getProgramGrade()->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGroup->getProgramGrade()->getId()]
            ],
            ['fixed' => $programGroup->getGroup()->__toString()],
            ['fixed' => $translator->trans('title.list', [], 'itp_student_program')]
        ];

        return $this->render('itp/training_program/student_program/clean.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }
}
