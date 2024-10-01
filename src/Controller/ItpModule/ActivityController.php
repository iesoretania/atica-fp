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
use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGradeLearningOutcome;
use App\Entity\Person;
use App\Form\Type\ItpModule\ActivityType;
use App\Repository\Edu\LearningOutcomeRepository;
use App\Repository\ItpModule\ActivityRepository;
use App\Repository\ItpModule\ProgramGradeLearningOutcomeRepository;
use App\Repository\ItpModule\SubjectRepository;
use App\Security\ItpModule\ActivityVoter;
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

#[Route(path: '/formacion/programa/curso/actividad')]
class ActivityController extends AbstractController
{
    #[Route(path: '/listar/{programGrade}/{page}', name: 'in_company_training_phase_activity_list', requirements: ['programGrade' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                   $request,
        TranslatorInterface       $translator,
        ActivityRepository        $activityRepository,
        ProgramGrade              $programGrade,
        int                       $page = 1
    ): Response
    {
        assert($programGrade->getGrade() instanceof Grade);
        assert($programGrade->getGrade()->getTraining() instanceof Training);
        $academicYear = $programGrade->getGrade()->getTraining()->getAcademicYear();
        assert($academicYear instanceof AcademicYear);
        $organization = $academicYear->getOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $q = $request->get('q');

        /** @var Person $person */
        $person = $this->getUser();

        $queryBuilder = $activityRepository->createActivityByProgramGradeQueryBuilder(
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

        $title = $translator->trans('title.list', [], 'itp_activity')
            . ' - ' . $programGrade->getGrade()->__toString();

        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            ['fixed' => $programGrade->getGrade()->getName()],
            ['fixed' => $translator->trans('title.detail', [], 'itp_activity')]
        ];

        return $this->render('itp/training_program/activity_list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_activity',
            'program_grade' => $programGrade
        ]);
    }

    #[Route(path: '/nueva/{programGrade}', name: 'in_company_training_phase_activity_new', requirements: ['programGrade' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request                               $request,
        TranslatorInterface                   $translator,
        ActivityRepository                    $activityRepository,
        SubjectRepository                     $itpSubjectRepository,
        LearningOutcomeRepository             $learningOutcomeRepository,
        ProgramGradeLearningOutcomeRepository $activityLearningOutcomeRepository,
        ProgramGrade                          $programGrade
    ): Response
    {
        assert($programGrade->getGrade() instanceof Grade);
        assert($programGrade->getGrade()->getTraining() instanceof Training);
        $academicYear = $programGrade->getGrade()->getTraining()->getAcademicYear();
        assert($academicYear instanceof AcademicYear);
        $organization = $academicYear->getOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGrade->getTrainingProgram());

        $activity = new Activity();
        $activity
            ->setProgramGrade($programGrade);

        $activityRepository->persist($activity);

        return $this->edit($request, $translator, $activityRepository, $itpSubjectRepository, $learningOutcomeRepository, $activityLearningOutcomeRepository, $activity);
    }

    #[Route(path: '/{id}', name: 'in_company_training_phase_activity_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                               $request,
        TranslatorInterface                   $translator,
        ActivityRepository                    $activityRepository,
        SubjectRepository                     $itpSubjectRepository,
        LearningOutcomeRepository             $learningOutcomeRepository,
        ProgramGradeLearningOutcomeRepository $activityLearningOutcomeRepository,
        Activity                              $activity
    ): Response {
        $this->denyAccessUnlessGranted(ActivityVoter::MANAGE, $activity);

        $subjects = $activity->getId() ? $itpSubjectRepository->findByActivity($activity) : [];

        $learningOutcomes = $learningOutcomeRepository->findBySubjects($subjects);
        $actualActivityLearningOutcomes = $activityLearningOutcomeRepository->findByActivity($activity);

        $activityLearningOutcomes = array_map(
            function (LearningOutcome $learningOutcome) use ($actualActivityLearningOutcomes, $activityLearningOutcomeRepository, $activity) {
                $activityLearningOutcome = null;
                foreach ($actualActivityLearningOutcomes as $aal) {
                    if ($aal->getLearningOutcome() === $learningOutcome) {
                        $activityLearningOutcome = $aal;
                        break;
                    }
                }
                if ($activityLearningOutcome === null) {
                    $activityLearningOutcome = new ProgramGradeLearningOutcome();
                    $activityLearningOutcome
                        ->setActivity($activity)
                        ->setShared(false)
                        ->setLearningOutcome($learningOutcome);
                    $activityLearningOutcomeRepository->persist($activityLearningOutcome);
                }
                return $activityLearningOutcome;
            },
            $learningOutcomes
        );

        $form = $this->createForm(ActivityType::class, $activity, [
            'activity_learning_outcomes' => $activityLearningOutcomes
        ]);

        // Obtener módulos que tengan CEs asociados a la actividad
        $form->get('subjects')->setData($subjects);

        $form->handleRequest($request);


        $learningOutcomes = $learningOutcomeRepository->findBySubjects($subjects);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $activityRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_training_program'));
                return $this->redirectToRoute('in_company_training_phase_activity_list', ['programGrade' => $activity->getProgramGrade()->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_training_program'));
            }
        }

        $title = $translator->trans(
            $activity->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'itp_activity'
        );

        $breadcrumb = [
            [
                'fixed' => $activity->getProgramGrade()->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $activity->getProgramGrade()->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $activity->getProgramGrade()->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_activity_list',
                'routeParams' => ['programGrade' => $activity->getProgramGrade()->getId()]
            ],
            ['fixed' => $activity->getCode() ?? $translator->trans('title.new', [], 'itp_activity')]
        ];

        return $this->render('itp/training_program/activity_form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'activity' => $activity,
            'form' => $form->createView()
        ]);
    }

    /*#[Route(path: '/eliminar/{academicYear}', name: 'in_company_training_phase_training_program_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
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
    }*/
}
