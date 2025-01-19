<?php

namespace App\Controller\ItpModule;

use App\Entity\Edu\Grade;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use App\Entity\ItpModule\TrainingProgram;
use App\Form\Model\ItpModule\CalendarCopy;
use App\Form\Type\ItpModule\CalendarCopyType;
use App\Form\Type\ItpModule\StudentProgramWorkcenterBatchType;
use App\Form\Type\ItpModule\StudentProgramWorkcenterType;
use App\Repository\ItpModule\StudentProgramRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterActivityRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\TrainingProgramVoter;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;


#[Route(path: '/formacion/plan/curso/grupo/gestion')]
class StudentProgramWorkcenterManagerController extends AbstractController
{
    #[Route(path: '/listar/{programGrade}/{page}', name: 'in_company_training_phase_student_program_workcenter_manage_list', requirements: ['programGrade' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function manage(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        ProgramGrade                       $programGrade,
        int                                $page = 1
    ): Response
    {
        assert($programGrade instanceof ProgramGrade);
        $trainingProgram = $programGrade->getTrainingProgram();
        assert($trainingProgram instanceof TrainingProgram);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $trainingProgram);

        $q = $request->get('q');

        $qb = $studentProgramWorkcenterRepository->createFindByProgramGradeAndFilterQueryBuilder($programGrade, $q);

        $adapter = new QueryAdapter($qb);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $grade = $programGrade->getGrade();
        assert($grade instanceof Grade);
        $title = $translator->trans('title.manage_student_programs', [], 'itp_student_program')
            . ' - ' . $grade->__toString();

        $breadcrumb = [
            [
                'fixed' => $trainingProgram->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $trainingProgram->getId()]
            ],
            [
                'fixed' => $grade->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            ['fixed' => $translator->trans('title.manage_student_programs', [], 'itp_student_program')]
        ];

        return $this->render('itp/training_program/workcenter/manage_list.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_student_program_workcenter',
            'program_grade' => $programGrade,
        ]);
    }

    #[Route(path: '/nueva/{programGroup}', name: 'in_company_training_phase_student_program_workcenter_manage_new', requirements: ['programGroup' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramRepository           $studentProgramRepository,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        StudentProgramWorkcenterActivityRepository $studentProgramWorkcenterActivityRepository,
        ProgramGroup $programGroup
    ): Response
    {
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $programGroup->getProgramGrade()->getTrainingProgram());

        $studentProgramWorkcenter = new StudentProgramWorkcenter();

        $form = $this->createForm(StudentProgramWorkcenterBatchType::class, $studentProgramWorkcenter, [
            'program_group' => $programGroup
        ]);
        $form->get('company')->setData($studentProgramWorkcenter->getWorkcenter()?->getCompany());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                foreach ($form->get('students')->getData() as $studentEnrollment) {
                    assert($studentEnrollment instanceof StudentEnrollment);
                    $studentProgram = $studentProgramRepository->findOneOrNewByStudentEnrollmentAndProgramGroup($studentEnrollment, $programGroup);
                    $newStudentProgramWorkcenter = new StudentProgramWorkcenter();
                    $studentProgramWorkcenterRepository->persist($newStudentProgramWorkcenter);
                    $newStudentProgramWorkcenter
                        ->setWorkcenter($studentProgramWorkcenter->getWorkcenter())
                        ->setStudentProgram($studentProgram)
                        ->setStartDate($studentProgramWorkcenter->getStartDate())
                        ->setEndDate($studentProgramWorkcenter->getEndDate())
                        ->setEducationalTutor($studentProgramWorkcenter->getEducationalTutor())
                        ->setWorkTutor($studentProgramWorkcenter->getWorkTutor())
                        ->setAdditionalEducationalTutor($studentProgramWorkcenter->getAdditionalEducationalTutor())
                        ->setAdditionalWorkTutor($studentProgramWorkcenter->getAdditionalWorkTutor());

                    // Añadir nuevas actividades
                    $selectedActivities = $form->get('selectedActivities')->getData();
                    foreach ($selectedActivities as $activity) {
                        $studentProgramWorkcenterActivity = new StudentProgramWorkcenterActivity();
                        $studentProgramWorkcenterActivity
                            ->setStudentProgramWorkcenter($newStudentProgramWorkcenter)
                            ->setActivity($activity)
                            ->setDisabled(false);
                        $studentProgramWorkcenterActivityRepository->persist($studentProgramWorkcenterActivity);
                    }
                }
                $studentProgramWorkcenterRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_student_program_workcenter'));
                return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_manage_list', ['programGrade' => $programGroup->getProgramGrade()->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_student_program_workcenter'));
            }
        }

        $title = $programGroup->getGroup()->__toString() . ' - ' . $translator->trans('title.batch_new', [], 'itp_student_program_workcenter');

        $trainingProgram = $programGroup->getProgramGrade()->getTrainingProgram();
        assert($trainingProgram instanceof TrainingProgram);

        $grade = $programGroup->getProgramGrade()->getGrade();
        assert($grade instanceof Grade);

        $breadcrumb = [
            [
                'fixed' => $trainingProgram->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $trainingProgram->getId()]
            ],
            [
                'fixed' => $grade->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGroup->getProgramGrade()?->getId()]
            ],
            [
                'fixed' => $title
            ]
        ];

        return $this->render('itp/training_program/workcenter/batch_form.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'student_program' => $studentProgramWorkcenter,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/detalle/{studentProgramWorkcenter}', name: 'in_company_training_phase_student_program_workcenter_manage_edit', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        StudentProgramWorkcenterActivityRepository $studentProgramWorkcenterActivityRepository,
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
        $currentActivities = $studentProgramWorkcenter->getActivities()->map(fn($activity) => $activity->getActivity())->toArray();
        $form->get('selectedActivities')->setData($currentActivities);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $selectedActivities = $form->get('selectedActivities')->getData();
                $currentStudentProgramWorkcenterActivities = $studentProgramWorkcenter->getActivities()->toArray();
                // Eliminar actividades deseleccionadas
                foreach ($currentStudentProgramWorkcenterActivities as $activity) {
                    if (!in_array($activity->getActivity(), $selectedActivities, true)) {
                        $studentProgramWorkcenterActivityRepository->remove($activity);
                    }
                }
                // Añadir nuevas actividades
                foreach ($selectedActivities as $activity) {
                    $found = false;
                    foreach ($currentStudentProgramWorkcenterActivities as $currentActivity) {
                        if ($currentActivity->getActivity() === $activity) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $studentProgramWorkcenterActivity = new StudentProgramWorkcenterActivity();
                        $studentProgramWorkcenterActivity
                            ->setStudentProgramWorkcenter($studentProgramWorkcenter)
                            ->setActivity($activity)
                            ->setDisabled(false);
                        $studentProgramWorkcenterActivityRepository->persist($studentProgramWorkcenterActivity);
                    }
                }
                $studentProgramWorkcenterRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_student_program_workcenter'));
                return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_manage_list', ['programGrade' => $programGrade->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_student_program_workcenter'));
            }
        }

        $title = ($studentProgramWorkcenter->getId() === null ? $translator->trans(
                    'title.new',
                    [],
                    'itp_student_program_workcenter'
                ) . ' - ' : '') . $studentProgram->getStudentEnrollment()->getPerson()->__toString() .  ' - ' . $programGroup->getGroup()->__toString();

        $trainingProgram = $programGrade->getTrainingProgram();
        assert($trainingProgram instanceof TrainingProgram);

        $grade = $programGrade->getGrade();
        assert($grade instanceof Grade);

        $breadcrumb = [
            [
                'fixed' => $trainingProgram->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $trainingProgram->getId()]
            ],
            [
                'fixed' => $grade->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            [
                'fixed' => $title
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
    #[Route(path: '/operacion/{programGrade}', name: 'in_company_training_phase_student_program_workcenter_manage_operation', requirements: ['programGrade' => '\d+'], methods: ['POST'])]
    public function operation(
        Request                            $request,
        TranslatorInterface                $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        ProgramGrade                       $programGrade
    ): Response {
        $trainingProgram = $programGrade->getTrainingProgram();
        assert($trainingProgram instanceof TrainingProgram);
        $this->denyAccessUnlessGranted(TrainingProgramVoter::MANAGE, $trainingProgram);

        $items = $request->request->all('items');
        if (count($items) === 0) {
            return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_manage_list', ['programGrade' => $programGrade->getId()]);
        }
        $selectedItems = $studentProgramWorkcenterRepository->findAllInListByIdAndProgramGrade($items, $programGrade);

        if (count($items) !== 0) {
            if ('' === $request->get('delete')) {
                return $this->delete($selectedItems, $request, $translator, $studentProgramWorkcenterRepository, $programGrade);
            }
            if ('' === $request->get('copy')) {
                return $this->copy($selectedItems, $request, $translator, $studentProgramWorkcenterRepository, $programGrade);
            }
        }
        return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_manage_list', ['programGrade' => $programGrade->getId()]);
}

    private function delete(
        array $items,
        Request $request,
        TranslatorInterface $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        ProgramGrade $programGrade): Response
    {
        if ($request->get('confirm', '') === 'ok') {
            try {
                $studentProgramWorkcenterRepository->deleteFromList($items);
                $studentProgramWorkcenterRepository->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'itp_student_program_workcenter'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'itp_student_program_workcenter'));
            }
            return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_manage_list', ['programGrade' => $programGrade->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'itp_student_program_workcenter');
        $grade = $programGrade->getGrade();
        assert($grade instanceof Grade);

        $trainingProgram = $programGrade->getTrainingProgram();
        assert($trainingProgram instanceof TrainingProgram);

        $breadcrumb = [
            [
                'fixed' => $trainingProgram->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $trainingProgram->getId()]
            ],
            [
                'fixed' => $grade->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            [
                'fixed' => $translator->trans('title.manage_student_programs', [], 'itp_student_program'),
                'routeName' => 'in_company_training_phase_student_program_workcenter_manage_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/workcenter/delete.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $items
        ]);
    }

    private function copy(
        array $items,
        Request $request,
        TranslatorInterface $translator,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        ProgramGrade $programGrade): Response
    {
        $studentProgramWorkcenterChoices = $studentProgramWorkcenterRepository->findAllInListByNotIdAndProgramGrade($items, $programGrade);
        $calendarCopy = new CalendarCopy();

        $form = $this->createForm(CalendarCopyType::class, $calendarCopy, [
            'student_program_workcenters' => $studentProgramWorkcenterChoices
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                foreach ($items as $studentProgramWorkcenter) {
                    $studentProgramWorkcenterRepository->cloneCalendarFromStudentProgramWorkcenter(
                        $studentProgramWorkcenter,
                        $calendarCopy->getSourceStudentProgramWorkcenter(),
                        $calendarCopy->getOverwriteAction() === CalendarCopy::OVERWRITE_ACTION_REPLACE
                    );
                }
                $studentProgramWorkcenterRepository->flush();
                foreach ($items as $studentProgramWorkcenter) {
                    $studentProgramWorkcenterRepository->updateDates($studentProgramWorkcenter);
                }
                $this->addFlash('success', $translator->trans('message.calendar_copied', [], 'itp_student_program_workcenter'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.calendar_copy_error', [], 'itp_student_program_workcenter'));
            }
            return $this->redirectToRoute('in_company_training_phase_student_program_workcenter_manage_list', ['programGrade' => $programGrade->getId()]);
        }

        $title = $translator->trans('title.calendar.copy', [], 'itp_student_program_workcenter');
        $grade = $programGrade->getGrade();
        assert($grade instanceof Grade);

        $trainingProgram = $programGrade->getTrainingProgram();
        assert($trainingProgram instanceof TrainingProgram);

        $breadcrumb = [
            [
                'fixed' => $trainingProgram->getName(),
                'routeName' => 'in_company_training_phase_grade_list',
                'routeParams' => ['trainingProgram' => $trainingProgram->getId()]
            ],
            [
                'fixed' => $grade->getName(),
                'routeName' => 'in_company_training_phase_group_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            [
                'fixed' => $translator->trans('title.manage_student_programs', [], 'itp_student_program'),
                'routeName' => 'in_company_training_phase_student_program_workcenter_manage_list',
                'routeParams' => ['programGrade' => $programGrade->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/workcenter/copy.html.twig', [
            'menu_path' => 'in_company_training_phase_training_program_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView(),
            'items' => $items
        ]);
    }
}
