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
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\Person;
use App\Form\Type\ItpModule\ActivityType;
use App\Repository\ItpModule\ActivityRepository;
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

#[Route(path: '/formacion/plan/curso/actividad')]
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

        return $this->render('itp/training_program/activity/list.html.twig', [
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

        return $this->edit($request, $translator, $activityRepository, $activity);
    }

    #[Route(path: '/{id}', name: 'in_company_training_phase_activity_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                               $request,
        TranslatorInterface                   $translator,
        ActivityRepository                    $activityRepository,
        Activity                              $activity
    ): Response {
        $this->denyAccessUnlessGranted(ActivityVoter::MANAGE, $activity);

        $form = $this->createForm(ActivityType::class, $activity);

        $form->handleRequest($request);

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

        return $this->render('itp/training_program/activity/form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'activity' => $activity,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/eliminar/{programGrade}', name: 'in_company_training_phase_activity_operation', requirements: ['programGrade' => '\d+'], methods: ['POST'])]
    public function operation(
        Request                   $request,
        TranslatorInterface       $translator,
        ActivityRepository        $activityRepository,
        ProgramGrade              $programGrade
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
            return $this->redirectToRoute('in_company_training_phase_activity_list', ['programGrade' => $programGrade->getId()]);
        }
        $selectedItems = $activityRepository->findAllInListByIdAndProgramGrade($items, $programGrade);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $activityRepository->deleteFromList($selectedItems);
                $activityRepository->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'itp_activity'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'itp_activity'));
            }
            return $this->redirectToRoute('in_company_training_phase_activity_list', ['programGrade' => $programGrade->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'itp_activity');

        $breadcrumb = [
            [
                'fixed' => $programGrade->getTrainingProgram()->getTraining()->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]
            ],
            [
                'fixed' => $programGrade->getGrade()->getName(),
                'routeName' => 'in_company_training_phase_activity_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/activity/delete.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }
}
