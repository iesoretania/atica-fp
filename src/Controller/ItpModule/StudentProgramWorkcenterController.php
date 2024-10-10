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

use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Person;
use App\Form\Type\ItpModule\StudentProgramWorkcenterType;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/plan/curso/grupo/estudiante/estancia')]
class StudentProgramWorkcenterController extends AbstractController
{
    #[Route(path: '/listar/{studentProgram}/{page}', name: 'in_company_training_phase_student_program_workcenter_list', requirements: ['studentProgram' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        StudentProgram                     $studentProgram,
        int                                $page = 1
    ): Response
    {
        assert($studentProgram instanceof StudentProgram);
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $q = $request->get('q');

        /** @var Person $person */
        $person = $this->getUser();

        $queryBuilder = $studentProgramWorkcenterRepository->createByStudentProgramQueryBuilder(
            $studentProgram,
            $q
        );

        $adapter = new QueryAdapter($queryBuilder, true);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'itp_student_program_workcenter')
            . ' - ' . $studentProgram->getStudentEnrollment()->getPerson()->__toString();

        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            [
                'fixed' => $programGroup->getGroup()->__toString(),
                'routeName' => 'in_company_training_phase_student_program_list',
                'routeParams' => ['programGroup' => $programGroup->getId()]
            ],
            ['fixed' => $studentProgram->getStudentEnrollment()->getPerson()->__toString()]
        ];

        return $this->render('itp/training_program/workcenter/list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_student_program',
            'student_program' => $studentProgram
        ]);
    }

    #[Route(path: '/nueva/{studentProgram}', name: 'in_company_training_phase_student_program_workcenter_new', requirements: ['studentProgram' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        StudentProgram $studentProgram
    ): Response
    {
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $studentProgramWorkcenter = new StudentProgramWorkcenter();
        $studentProgramWorkcenter
            ->setStudentProgram($studentProgram);

        $studentProgramWorkcenterRepository->persist($studentProgramWorkcenter);

        return $this->edit($request, $translator, $studentProgramWorkcenterRepository, $studentProgramWorkcenter);
    }

    #[Route(path: '/{studentProgramWorkcenter}', name: 'in_company_training_phase_student_program_workcenter_edit', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        StudentProgramWorkcenter           $studentProgramWorkcenter
    ): Response {
        $studentProgram = $studentProgramWorkcenter->getStudentProgram();
        assert($studentProgram instanceof StudentProgram);
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $form = $this->createForm(StudentProgramWorkcenterType::class, $studentProgramWorkcenter);
        $form->get('company')->setData($studentProgramWorkcenter->getWorkcenter()?->getCompany());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $studentProgramWorkcenterRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_student_program_workcenter'));
                return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_list', ['studentProgram' => $studentProgram->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_student_program_workcenter'));
            }
        }

        $title = ($studentProgramWorkcenter->getId() === null ? $translator->trans(
            'title.new',
            [],
            'itp_student_program_workcenter'
        ) . ' - ' : '') . $studentProgram->getStudentEnrollment()->getPerson()->__toString() .  ' - ' . $programGroup->getGroup()->__toString();

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
                'fixed' => $translator->trans($studentProgramWorkcenter->getId() ? 'title.edit' : 'title.new', [], 'itp_student_program_workcenter')
            ]
        ];

        return $this->render('itp/training_program/workcenter/form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'student_program' => $studentProgramWorkcenter,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/eliminar/{studentProgram}', name: 'in_company_training_phase_student_program_workcenter_operation', requirements: ['studentProgram' => '\d+'], methods: ['POST'])]
    public function operation(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        StudentProgram                     $studentProgram
    ): Response {
        $programGroup = $studentProgram->getProgramGroup();
        assert($programGroup instanceof ProgramGroup);
        $programGrade = $programGroup->getProgramGrade();
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_list', ['studentProgram' => $studentProgram->getId()]);
        }
        $selectedItems = $studentProgramWorkcenterRepository->findAllInListByIdAndStudentProgram($items, $studentProgram);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $studentProgramWorkcenterRepository->deleteFromList($selectedItems);
                $studentProgramWorkcenterRepository->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'itp_student_program_workcenter'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'itp_student_program_workcenter'));
            }
            return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_list', ['studentProgram' => $studentProgram->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'itp_student_program_workcenter');

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
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/workcenter/delete.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }
}
