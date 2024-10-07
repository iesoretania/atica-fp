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
use App\Form\Type\ItpModule\ProgramGroupType;
use App\Repository\ItpModule\ProgramGroupRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use Pagerfanta\Adapter\ArrayAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/plan/grupo')]
class GroupController extends AbstractController
{
    #[Route(path: '/listar/{programGrade}/{page}', name: 'in_company_training_phase_group_list', requirements: ['programGrade' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        TranslatorInterface    $translator,
        ProgramGroupRepository $programGroupRepository,
        ProgramGrade           $programGrade,
        int                    $page = 1
    ): Response
    {
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $programGrades = $programGroupRepository->findAllByProgramGrade($programGrade);

        $adapter = new ArrayAdapter($programGrades);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'itp_group')
            . ' - ' . $programGrade->getGrade()->__toString();

        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            ['fixed' => $programGrade->getGrade()->getName()],
            ['fixed' => $translator->trans('title.list', [], 'itp_group')]
        ];

        return $this->render('itp/training_program/group/list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'domain' => 'itp_group',
            'program_grade' => $programGrade,
        ]);
    }

    #[Route(path: '/{id}', name: 'in_company_training_phase_group_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        TranslatorInterface    $translator,
        ProgramGroupRepository $programGroupRepository,
        ProgramGroup           $programGroup
    ): Response {
        assert($programGroup->getProgramGrade() instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGroup->getProgramGrade()->getTrainingProgram());

        $form = $this->createForm(ProgramGroupType::class, $programGroup);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $programGroupRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_group'));
                return $this->redirectToRoute('in_company_training_phase_group_list', ['programGrade' => $programGroup->getProgramGrade()->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_group'));
            }
        }

        $title = $translator->trans('title.edit', [], 'itp_company');

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
            ['fixed' => $programGroup->getGroup()->__toString()]
        ];

        return $this->render('itp/training_program/company/form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'company_program' => $programGroup,
            'form' => $form->createView()
        ]);
    }
}
