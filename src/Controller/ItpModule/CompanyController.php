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
use App\Entity\ItpModule\CompanyProgram;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\Person;
use App\Form\Type\ItpModule\CompanyProgramType;
use App\Repository\ItpModule\CompanyProgramRepository;
use App\Repository\ItpModule\CompanyRepository as ItpCompanyRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TrainingProgramVoter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/plan/empresa')]
class CompanyController extends AbstractController
{
    #[Route(path: '/listar/{programGrade}/{page}', name: 'in_company_training_phase_company_list', requirements: ['programGrade' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                  $request,
        TranslatorInterface      $translator,
        CompanyProgramRepository $companyProgramRepository,
        ProgramGrade             $programGrade,
        int                      $page = 1
    ): Response
    {
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $q = $request->get('q');

        /** @var Person $person */
        $person = $this->getUser();

        $queryBuilder = $companyProgramRepository->createByProgramGradeQueryBuilder(
            $programGrade,
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

        $stats = $companyProgramRepository->getCompanyProgramStats($programGrade);

        $title = $translator->trans('title.list', [], 'itp_company')
            . ' - ' . $programGrade->getGrade()->__toString();

        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            ['fixed' => $programGrade->getGrade()->getName()],
            ['fixed' => $translator->trans('title.list', [], 'itp_company')]
        ];

        return $this->render('itp/training_program/company/list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_company',
            'program_grade' => $programGrade,
            'stats' => $stats
        ]);
    }

    #[Route(path: '/nueva/{programGrade}', name: 'in_company_training_phase_company_new', requirements: ['programGrade' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request                  $request,
        TranslatorInterface      $translator,
        ItpCompanyRepository     $itpCompanyRepository,
        CompanyProgramRepository $companyProgramRepository,
        ProgramGrade             $programGrade
    ): Response
    {
        assert($programGrade instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $companyProgram = new CompanyProgram();
        $companyProgram
            ->setProgramGrade($programGrade);

        $companyProgramRepository->persist($companyProgram);

        return $this->edit($request, $translator, $itpCompanyRepository, $companyProgramRepository, $companyProgram);
    }

    #[Route(path: '/{id}', name: 'in_company_training_phase_company_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                  $request,
        TranslatorInterface      $translator,
        ItpCompanyRepository     $itpCompanyRepository,
        CompanyProgramRepository $companyProgramRepository,
        CompanyProgram           $companyProgram
    ): Response {
        assert($companyProgram->getProgramGrade() instanceof ProgramGrade);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $companyProgram->getProgramGrade()->getTrainingProgram());

        $form = $this->createForm(CompanyProgramType::class, $companyProgram);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $companyProgramRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_company'));
                return $this->redirectToRoute('in_company_training_phase_company_list', ['programGrade' => $companyProgram->getProgramGrade()->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_company'));
            }
        }

        $title = $translator->trans(
            $companyProgram->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'itp_company'
        );

        $breadcrumb = [
            [
                'fixed' => $companyProgram->getProgramGrade()->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $companyProgram->getProgramGrade()->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $companyProgram->getProgramGrade()->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_company_list',
                'routeParams' => ['programGrade' => $companyProgram->getProgramGrade()->getId()]
            ],
            ['fixed' => $companyProgram->getId() ? $companyProgram->getCompany() : $translator->trans('title.new', [], 'itp_company')]
        ];

        return $this->render('itp/training_program/company/form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'company_program' => $companyProgram,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/eliminar/{programGrade}', name: 'in_company_training_phase_company_operation', requirements: ['programGrade' => '\d+'], methods: ['POST'])]
    public function operation(
        Request                  $request,
        TranslatorInterface      $translator,
        CompanyProgramRepository $companyProgramRepository,
        ProgramGrade             $programGrade
    ): Response {
        assert($programGrade->getGrade() instanceof Grade);
        assert($programGrade->getGrade()->getTraining() instanceof Training);
        $academicYear = $programGrade->getGrade()->getTraining()->getAcademicYear();
        assert($academicYear instanceof AcademicYear);
        $organization = $academicYear->getOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute('in_company_training_phase_company_list', ['programGrade' => $programGrade->getId()]);
        }
        $selectedItems = $companyProgramRepository->findAllInListByIdAndProgramGrade($items, $programGrade);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $companyProgramRepository->deleteFromList($selectedItems);
                $companyProgramRepository->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'itp_company'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'itp_company'));
            }
            return $this->redirectToRoute('in_company_training_phase_company_list', ['programGrade' => $programGrade->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'itp_company');

        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_company_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/company/delete.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }
}
