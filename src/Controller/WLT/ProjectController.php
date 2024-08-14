<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Controller\WLT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Entity\WLT\Project;
use App\Form\Type\WLT\ProjectStudentEnrollmentType;
use App\Form\Type\WLT\ProjectType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WLT\ActivityRealizationGradeRepository;
use App\Repository\WLT\ActivityRepository;
use App\Repository\WLT\AgreementActivityRealizationRepository;
use App\Repository\WLT\AgreementRepository;
use App\Repository\WLT\ContactRepository;
use App\Repository\WLT\LearningProgramRepository;
use App\Repository\WLT\MeetingRepository;
use App\Repository\WLT\ProjectRepository;
use App\Security\OrganizationVoter;
use App\Security\WLT\ProjectVoter;
use App\Security\WLT\WLTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/dual/proyecto')]
class ProjectController extends AbstractController
{
    #[Route(path: '/listar/{academicYear}/{page}', name: 'work_linked_training_project_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('p')
            ->distinct()
            ->from(Project::class, 'p')
            ->leftJoin('p.manager', 'm')
            ->join('p.groups', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('p.name');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('p.name LIKE :tq')
                ->orWhere('p.name LIKE :tq')
                ->orWhere('m.firstName LIKE :tq')
                ->orWhere('m.lastName LIKE :tq')
                ->orWhere('m.uniqueIdentifier LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('p.organization = :organization')
            ->setParameter('organization', $organization);

        if (!$isManager) {
            $queryBuilder
                ->andWhere('p.manager = :manager OR (d.head IS NOT NULL AND h.person = :manager)')
                ->setParameter('manager', $this->getUser());
        }

        $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wlt_project');

        return $this->render('wlt/project/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_project',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/nuevo', name: 'work_linked_training_project_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $project = new Project();
        $project
            ->setOrganization($organization)
            ->setLocked(false);

        if (!$isManager) {
            /** @var Person $manager */
            $manager = $this->getUser();
            $project
                ->setManager($manager);
        }

        $managerRegistry->getManager()->persist($project);

        return $this->edit($request, $userExtensionService, $translator, $managerRegistry,  $project);
    }

    #[Route(path: '/{id}', name: 'work_linked_training_project_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::MANAGE, $project);

        $organization = $userExtensionService->getCurrentOrganization();
        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $em = $managerRegistry->getManager();

        $form = $this->createForm(ProjectType::class, $project, [
            'lock_manager' => !$isManager
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_project'));
                return $this->redirectToRoute('work_linked_training_project_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_project'));
            }
        }

        $title = $translator->trans(
            $project->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wlt_project'
        );

        $breadcrumb = [
            $project->getId() !== null ?
                ['fixed' => $project->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'wlt_project')]
        ];

        return $this->render('wlt/project/form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/estudiantes/{id}', name: 'work_linked_training_project_student_enrollment', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function studentEnrollments(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Project $project
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $em = $managerRegistry->getManager();

        $form = $this->createForm(ProjectStudentEnrollmentType::class, $project, [
            'groups' => $project->getGroups()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_project'));
                return $this->redirectToRoute('work_linked_training_project_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_project'));
            }
        }

        $title = $translator->trans(
            'title.student_enrollment',
            [],
            'wlt_project'
        );

        $breadcrumb = [
            ['fixed' => $project->getName()],
            ['fixed' => $translator->trans('title.student_enrollment', [], 'wlt_project')]
        ];

        return $this->render('wlt/project/student_enrollment_form.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/eliminar', name: 'work_linked_training_project_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        ProjectRepository $projectRepository,
        AgreementRepository $agreementRepository,
        ActivityRepository $activityRepository,
        LearningProgramRepository $learningProgramRepository,
        AgreementActivityRealizationRepository $agreementActivityRealizationRepository,
        ActivityRealizationGradeRepository $activityRealizationGradeRepository,
        MeetingRepository $meetingRepository,
        ContactRepository $contactRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('work_linked_training_project_list');
        }
        $selectedItems = $projectRepository->findAllInListByIdAndOrganization($items, $organization);

        if ($request->get('confirm', '') === 'ok') {
            try {
                /** @var Project $selectedItem */
                foreach ($selectedItems as $selectedItem) {
                    $agreementActivityRealizationRepository->deleteFromAgreementList($selectedItem->getAgreements());
                }
                $meetingRepository->deleteFromProjects($selectedItems);
                $contactRepository->deleteFromProjects($selectedItems);
                $agreementRepository->deleteFromProjects($selectedItems);
                foreach ($selectedItems as $selectedItem) {
                    $project = $projectRepository->find($selectedItem);
                    $project->getStudentEnrollments()->clear();
                    $activityRepository->deleteFromList($project->getActivities());
                }
                $learningProgramRepository->deleteFromProjects($selectedItems);
                $activityRealizationGradeRepository->deleteFromProjects($selectedItems);
                $projectRepository->deleteFromList($selectedItems);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_project'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_project'));
            }
            return $this->redirectToRoute('work_linked_training_project_list');
        }

        $title = $translator->trans('title.delete', [], 'wlt_project');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/project/delete.html.twig', [
            'menu_path' => 'work_linked_training_project_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }
}
