<?php

namespace App\Controller\ItpModule;

use App\Entity\Edu\Grade;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\TrainingProgram;
use App\Form\Model\ItpModule\CalendarCopy;
use App\Form\Type\ItpModule\CalendarCopyType;
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

        $training = $trainingProgram->getTraining();
        assert($training instanceof Training);
        $breadcrumb = [
            [
                'fixed' => $training->getName(),
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
                'fixed' => $trainingProgram->getTraining()->getName(),
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
                'fixed' => $trainingProgram->getTraining()->getName(),
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