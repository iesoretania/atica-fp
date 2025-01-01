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

use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\TrainingProgram;
use App\Form\Type\ItpModule\ProgramGradeType;
use App\Repository\ItpModule\ProgramGradeRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Service\UserExtensionService;
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
        ProgramGradeRepository $programGradeRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        TrainingProgram $trainingProgram,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $trainingProgram);

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
            . ' - ' . $trainingProgram->getName();

        $breadcrumb = [
            ['fixed' => $trainingProgram->getName()],
            ['fixed' => $translator->trans('title.detail', [], 'itp_training_program')]
        ];

        return $this->render('itp/training_program/grade/list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'domain' => 'itp_training_program',
            'training_program' => $trainingProgram
        ]);
    }
    #[Route(path: '/resultados/{programGrade}', name: 'in_company_training_phase_grade_detail_edit', requirements: ['programGrade' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                $request,
        TranslatorInterface    $translator,
        ProgramGradeRepository $programGradeRepository,
        UserExtensionService   $userExtensionService,
        ProgramGrade           $programGrade
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $trainingProgram = $programGrade->getTrainingProgram();
        assert($trainingProgram instanceof TrainingProgram);

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $trainingProgram);

        $form = $this->createForm(ProgramGradeType::class, $programGrade);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $programGradeRepository->flush();
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
                'fixed' => $trainingProgram->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $trainingProgram->getId()]
            ],
            ['fixed' => $programGrade->getGrade()?->getName()],
            ['fixed' => $translator->trans('title.learning_outcomes', [], 'itp_grade')]
        ];

        return $this->render('itp/training_program/grade/learning_outcome_form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'domain' => 'itp_grade',
            'program_grade' => $programGrade
        ]);
    }
}
