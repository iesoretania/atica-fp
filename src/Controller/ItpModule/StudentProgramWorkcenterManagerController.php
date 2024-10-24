<?php

namespace App\Controller\ItpModule;

use App\Entity\Edu\Grade;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\TrainingProgram;
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
}