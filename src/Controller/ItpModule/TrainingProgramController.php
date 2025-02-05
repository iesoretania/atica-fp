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

use App\Entity\Edu\AcademicYear;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Form\Type\ItpModule\TrainingProgramType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\ItpModule\GroupRepository;
use App\Repository\ItpModule\ProgramGradeRepository;
use App\Repository\ItpModule\ProgramGroupRepository;
use App\Repository\ItpModule\TrainingProgramRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/formacion/plan')]
class TrainingProgramController extends AbstractController
{
    #[Route(path: '/listar/{academicYear}/{page}', name: 'in_company_training_phase_training_program_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        TrainingProgramRepository $trainingProgramRepository,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        TranslatorInterface $translator,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $q = $request->get('q');

        /** @var Person $person */
        $person = $this->getUser();
        $teacher = $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        // Precargar grupos y niveles de los programas formativos del curso académico
        $groupRepository->findByAcademicYear($academicYear);

        $queryBuilder = $trainingProgramRepository->createProgramRepositoryQueryBuilder(
            $academicYear,
            $isManager,
            $person,
            $teacher,
            $q
        );

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'itp_training_program');

        return $this->render('itp/training_program/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_training_program',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/nuevo/{academicYear}', name: 'in_company_training_phase_training_program_new', requirements: ['academicYear' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        TrainingProgramRepository $trainingProgramRepository,
        ProgramGradeRepository $programGradeRepository,
        ProgramGroupRepository $programGroupRepository,
        AcademicYear $academicYear
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);

        $trainingProgram = new TrainingProgram();
        $trainingProgram
            ->setModality(TrainingProgram::MODE_GENERAL)
            ->setLocked(false)
            ->setTotalHours(200000);

        $trainingProgramRepository->persist($trainingProgram);

        return $this->edit($request, $userExtensionService, $translator, $trainingProgramRepository, $programGradeRepository, $programGroupRepository, $trainingProgram, $academicYear);
    }

    #[Route(path: '/{trainingProgram}/{academicYear}', name: 'in_company_training_phase_training_program_edit', requirements: ['trainingProgram' => '\d+', 'academicYear' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request              $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface  $translator,
        TrainingProgramRepository $trainingProgramRepository,
        ProgramGradeRepository $programGradeRepository,
        ProgramGroupRepository $programGroupRepository,
        TrainingProgram      $trainingProgram,
        AcademicYear         $academicYear = null
    ): Response {
        if ($trainingProgram->getId()) {
            $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $trainingProgram);
        }

        $organization = $userExtensionService->getCurrentOrganization();
        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);
        $canManagePermissions = $this->isGranted(TrainingProgramVoter::MANAGE_PERMISSIONS, $trainingProgram)
            || $trainingProgram->getId() === null;

        $form = $this->createForm(TrainingProgramType::class, $trainingProgram, [
            'lock_manager' => !$isManager,
            'new' => $trainingProgram->getId() === null,
            'is_manager' => $isManager,
            'can_manage_permissions' => $canManagePermissions
        ]);

        $oldGrades = [];
        foreach ($trainingProgram->getTrainingProgramGrades() as $trainingProgramGrade) {
            $oldGrades[] = $trainingProgramGrade->getGrade();
        }

        $form->get('grades')->setData($oldGrades);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $currentGrades = $form->get('grades')->getData();
                $newGrades = array_diff($currentGrades, $oldGrades);
                $deletedGrades = array_diff($oldGrades, $currentGrades);
                foreach ($newGrades as $grade) {
                    $trainingProgramGrade = new ProgramGrade();
                    $trainingProgramGrade
                        ->setTrainingProgram($trainingProgram)
                        ->setGrade($grade);
                    $programGradeRepository->persist($trainingProgramGrade);
                    foreach ($grade->getGroups() as $group) {
                        $programGroup = new ProgramGroup();
                        $programGroup
                            ->setProgramGrade($trainingProgramGrade)
                            ->setGroup($group);
                        $programGroupRepository->persist($programGroup);
                    }
                }
                if ($trainingProgram->getId() !== null) {
                    $programGradeRepository->deleteFromGradeListAndTrainingProgram($deletedGrades, $trainingProgram);
                }
                $programGradeRepository->flush();
                $trainingProgramRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_training_program'));
                return $this->redirectToRoute('in_company_training_phase_training_program_list',
                    $academicYear !== null ? ['academicYear' => $academicYear->getId()] : []
                );
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_training_program'));
            }
        }

        $title = $translator->trans(
            $trainingProgram->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'itp_training_program'
        );

        $breadcrumb = [
            $trainingProgram->getId() !== null ?
                ['fixed' => $trainingProgram->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'itp_training_program')]
        ];

        return $this->render('itp/training_program/form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'academic_year' => $academicYear,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/eliminar/{academicYear}', name: 'in_company_training_phase_training_program_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function operation(
        Request                   $request,
        TrainingProgramRepository $trainingProgramRepository,
        UserExtensionService      $userExtensionService,
        TranslatorInterface       $translator,
        ManagerRegistry           $managerRegistry,
        AcademicYear              $academicYear
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute('in_company_training_phase_training_program_list', ['academicYear' => $academicYear->getId()]);
        }
        $selectedItems = $trainingProgramRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $trainingProgramRepository->deleteFromList($selectedItems);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'itp_training_program'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'itp_training_program'));
            }
            return $this->redirectToRoute('in_company_training_phase_training_program_list', ['academicYear' => $academicYear->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'itp_training_program');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/delete.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }
}
