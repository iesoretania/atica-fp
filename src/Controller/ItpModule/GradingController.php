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
use App\Entity\Edu\PerformanceScaleValue;
use App\Entity\Edu\ReportTemplate;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use App\Entity\ItpModule\StudentProgramWorkcenterActivityComment;
use App\Entity\Person;
use App\Entity\WltModule\AgreementActivityRealizationComment;
use App\Form\Type\ItpModule\StudentProgramWorkcenterActivityNewCommentType;
use App\Form\Type\ItpModule\StudentProgramWorkcenterGradeType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\PerformanceScaleValueRepository;
use App\Repository\ItpModule\ActivityRepository;
use App\Repository\ItpModule\CriterionRepository;
use App\Repository\ItpModule\ProgramGroupRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterActivityCommentRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterActivityRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\StudentProgramWorkcenterActivityCommentVoter;
use App\Security\ItpModule\StudentProgramWorkcenterVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

#[Route(path: '/formacion/valoracion', name: 'in_company_training_phase_tracking_grading_')]
class GradingController extends AbstractController
{
    #[Route(path: '/listar/{academicYear}/{page}', name: 'list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    final public function list(
        Request                             $request,
        UserExtensionService                $userExtensionService,
        TranslatorInterface                 $translator,
        AcademicYearRepository              $academicYearRepository,
        StudentProgramWorkcenterRepository  $studentProgramWorkcenterRepository,
        AcademicYear                        $academicYear = null,
        int                                 $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_SECTION, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $q = $request->get('q');
        $person = $this->getUser();
        assert($person instanceof Person);

        $queryBuilder = $studentProgramWorkcenterRepository->createGradingQueryBuilder(
            $academicYear,
            $person,
            $isManager,
            $q
        );
        assert($queryBuilder instanceof QueryBuilder);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'itp_grading');

        return $this->render('itp/training_program/grading/list.html.twig', [
            'title' => $title,
            'url_path' => 'in_company_training_phase_tracking_grading_form',
            'report_path' => 'in_company_training_phase_tracking_grading_report',
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_tracking',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/{studentProgramWorkcenter}', name: 'form', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['GET', 'POST'])]
    final public function index(
        Request                                    $request,
        TranslatorInterface                        $translator,
        PerformanceScaleValueRepository            $performanceScaleValueRepository,
        StudentProgramWorkcenterActivityRepository $studentProgramWorkcenterActivityRepository,
        StudentProgramWorkcenterRepository         $studentProgramWorkcenterRepository,
        StudentProgramWorkcenter                   $studentProgramWorkcenter
    ): Response {
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::VIEW_GRADE, $studentProgramWorkcenter);

        $academicYear = $studentProgramWorkcenter
            ->getStudentProgram()?->getStudentEnrollment()?->getGroup()?->getGrade()?->getTraining()?->getAcademicYear();
        assert($academicYear instanceof AcademicYear);

        // Pre-caching
        $studentProgramWorkcenterActivityRepository->findByStudentProgramWorkcenter($studentProgramWorkcenter);

        $readOnly = !$this->isGranted(StudentProgramWorkcenterVoter::GRADE, $studentProgramWorkcenter);

        $form = $this->createForm(StudentProgramWorkcenterGradeType::class, $studentProgramWorkcenter, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        $grades = $performanceScaleValueRepository->findByPerformanceScale($studentProgramWorkcenter
            ->getStudentProgram()?->getProgramGroup()?->getProgramGrade()
            ?->getTrainingProgram()?->getPerformanceScale());

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $studentProgramWorkcenterRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'itp_grading'));
                return $this->redirectToRoute('in_company_training_phase_tracking_grading_list', [
                    'academicYear' => $academicYear->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'itp_grading'));
            }
        }

        $title = $translator->trans('title.grade', [], 'wlt_agreement_activity_realization');

        $breadcrumb = [
            ['fixed' => $studentProgramWorkcenter->__toString()],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/grading/form.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_grading_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'grades' => $grades,
            'student_program_workcenter' => $studentProgramWorkcenter,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/comentarios/{studentProgramWorkcenterActivity}', name: 'comment_form', requirements: ['student_program_workcenter_activity' => '\d+'], methods: ['GET', 'POST'])]
    final public function comment(
        Request $request,
        TranslatorInterface $translator,
        StudentProgramWorkcenterActivityCommentRepository $studentProgramWorkcenterActivityCommentRepository,
        StudentProgramWorkcenterActivity $studentProgramWorkcenterActivity
    ): Response {
        $studentProgramWorkcenter = $studentProgramWorkcenterActivity->getStudentProgramWorkcenter();
        assert($studentProgramWorkcenter instanceof StudentProgramWorkcenter);
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::VIEW_GRADE, $studentProgramWorkcenter);

        $readOnly = !$this->isGranted(StudentProgramWorkcenterVoter::GRADE, $studentProgramWorkcenter);

        $form = $this->createForm(StudentProgramWorkcenterActivityNewCommentType::class, $studentProgramWorkcenterActivity, [
            'disabled' => $readOnly,
            'can_be_disabled' => !$studentProgramWorkcenterActivity->getScaleValue() instanceof PerformanceScaleValue
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $newComment = null;
                if (trim($form->get('newComment')->getData()) !== '') {
                    $person = $this->getUser();
                    assert($person instanceof Person);
                    $newComment = new StudentProgramWorkcenterActivityComment();
                    $studentProgramWorkcenterActivityCommentRepository->persist($newComment);
                    $newComment
                        ->setStudentProgramActivity($studentProgramWorkcenterActivity)
                        ->setComment(trim($form->get('newComment')->getData()))
                        ->setTimestamp(new \DateTimeImmutable())
                        ->setPerson($person);
                }
                $studentProgramWorkcenterActivityCommentRepository->flush();
                $this->addFlash('success', $translator->trans('message.saved', [],
                    'itp_grading'));

                if (!$newComment instanceof AgreementActivityRealizationComment) {
                    return $this->redirectToRoute('in_company_training_phase_tracking_grading_form', [
                        'studentProgramWorkcenter' => $studentProgramWorkcenter->getId()
                    ]);
                }

                return $this->redirectToRoute('in_company_training_phase_tracking_grading_comment_form', [
                    'studentProgramWorkcenterActivity' => $studentProgramWorkcenterActivity->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [],
                    'itp_grading'));
            }
        }

        $title = $translator->trans('title.comment', [], 'itp_grading');

        $breadcrumb = [
            [
                'fixed' => $studentProgramWorkcenter->__toString(),
                'routeName' => 'in_company_training_phase_tracking_grading_form',
                'routeParams' => ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            ],
            ['fixed' => $studentProgramWorkcenterActivity->getActivity()?->__toString() ?? ''],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/grading/comment_form.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_grading_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'student_program_workcenter' => $studentProgramWorkcenter,
            'student_program_workcenter_activity' => $studentProgramWorkcenterActivity,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/comentarios/eliminar/{activityComment}', name: 'comment_delete', requirements: ['activityComment' => '\d+'], methods: ['GET', 'POST'])]
    final public function deleteComment(
        Request $request,
        TranslatorInterface $translator,
        StudentProgramWorkcenterActivityCommentRepository $studentProgramWorkcenterActivityCommentRepository,
        StudentProgramWorkcenterActivityComment $activityComment
    ): Response {
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterActivityCommentVoter::DELETE,
            $activityComment);

        $studentProgramWorkcenterActivity = $activityComment->getStudentProgramActivity();
        assert($studentProgramWorkcenterActivity instanceof StudentProgramWorkcenterActivity);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $studentProgramWorkcenterActivityCommentRepository->remove($activityComment);
                $studentProgramWorkcenterActivityCommentRepository->flush();
                $this->addFlash('success', $translator->trans('message.comment_deleted', [],
                    'wlt_agreement_activity_realization'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.comment_delete_error', [],
                    'wlt_agreement_activity_realization'));
            }
            return $this->redirectToRoute('in_company_training_phase_tracking_grading_comment_form', [
                'studentProgramWorkcenterActivity' => $studentProgramWorkcenterActivity->getId()
            ]);
        }

        $studentProgramWorkcenter = $studentProgramWorkcenterActivity->getStudentProgramWorkcenter();
        assert($studentProgramWorkcenter instanceof StudentProgramWorkcenter);

        $title = $translator->trans('title.delete_comment', [], 'itp_grading');

        $breadcrumb = [
            [
                'fixed' => $studentProgramWorkcenter->__toString(),
                'routeName' => 'in_company_training_phase_tracking_grading_form',
                'routeParams' => ['studentProgramWorkcenter' => $studentProgramWorkcenter->getId()]
            ],
            ['fixed' => $studentProgramWorkcenterActivity->getActivity()?->__toString() ?? ''],
            ['fixed' => $title]
        ];

        return $this->render('itp/training_program/grading/comment_delete.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_grading_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'comment' => $activityComment
        ]);
    }

    #[Route(path: '/informe/{studentProgramWorkcenter}', name: 'report', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['GET'])]
    final public function gradingReport(
        TranslatorInterface         $translator,
        Environment                 $engine,
        PerformanceScaleValueRepository $performanceScaleValueRepository,
        StudentProgramWorkcenterActivityRepository $studentProgramWorkcenterActivityRepository,
        StudentProgramWorkcenter    $studentProgramWorkcenter
    ): Response {
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::VIEW_GRADE, $studentProgramWorkcenter);

        $academicYear = $studentProgramWorkcenter
            ->getStudentProgram()?->getStudentEnrollment()?->getGroup()?->getGrade()?->getTraining()?->getAcademicYear();
        assert($academicYear instanceof AcademicYear);

        // Pre-caching
        $studentProgramWorkcenterActivityRepository->findByStudentProgramWorkcenter($studentProgramWorkcenter);

        $grades = $performanceScaleValueRepository->findByPerformanceScale($studentProgramWorkcenter
            ->getStudentProgram()?->getProgramGroup()?->getProgramGrade()
            ?->getTrainingProgram()?->getPerformanceScale());

        $title = $translator->trans('title.report', [], 'itp_grading')
            . ' - ' . $studentProgramWorkcenter->__toString();

        $fileName = $title . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        assert($mpdf instanceof Mpdf);
        $tmp = '';

        try {
            $template = $studentProgramWorkcenter->getStudentProgram()?->getProgramGroup()?->getProgramGrade()?->getTrainingProgram()?->getFinalReportTemplate() ??
                $studentProgramWorkcenter->getStudentProgram()
                ?->getStudentEnrollment()?->getGroup()?->getGrade()?->getTraining()?->getAcademicYear()?->getDefaultPortraitTemplate();

            if ($template instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }

            $mpdf->SetFont('DejaVuSansCondensed');
            $mpdf->SetFontSize(9);

            $mpdf->WriteHTML($engine->render('itp/training_program/grading/report.html.twig', [
                'student_program_workcenter' => $studentProgramWorkcenter,
                'academic_year' => $academicYear,
                'grades' => $grades
            ]));

            $mpdf->SetTitle($title);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/pdf');
            $response->setContent($mpdf->Output($fileName, Destination::STRING_RETURN));

            $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

            return $response;
        } finally {
            if ($tmp) {
                unlink($tmp);
            }
        }
    }

    #[Route(path: '/evaluacion/{academicYear}/{page}', name: 'group_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function groupList(
        Request                $request,
        TranslatorInterface    $translator,
        UserExtensionService   $userExtensionService,
        ProgramGroupRepository $programGroupRepository,
        StudentProgramWorkcenterRepository  $studentProgramWorkcenterRepository,
        AcademicYearRepository $academicYearRepository,
        AcademicYear           $academicYear = null,
        int                    $page = 1
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_VIEW_EVALUATION, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $q = $request->get('q');
        $person = $this->getUser();
        assert($person instanceof Person);

        $studentProgramWorkcenters = $studentProgramWorkcenterRepository->findByAcademicYearPersonManagerAndQuery($academicYear, $person, $isManager, null);
        $queryBuilder = $programGroupRepository->createGroupsFromStudentProgramWorkcenterStatsQueryBuilder($studentProgramWorkcenters, $q);

        $adapter = new QueryAdapter($queryBuilder);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.group_list', [], 'itp_grading');

        $breadcrumb = [
            ['fixed' => $translator->trans('title.group_list', [], 'itp_grading')]
        ];

        return $this->render('itp/training_program/grading/group_list.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_grading_group_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_grading',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/evaluacion/grupo/{programGroup}/{page}', name: 'group_student_list', requirements: ['programGroup' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    final public function groupStudentList(
        Request                             $request,
        UserExtensionService                $userExtensionService,
        TranslatorInterface                 $translator,
        StudentProgramWorkcenterRepository  $studentProgramWorkcenterRepository,
        ProgramGroup                        $programGroup,
        int                                 $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_SECTION, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $q = $request->get('q');
        $person = $this->getUser();
        assert($person instanceof Person);

        $queryBuilder = $studentProgramWorkcenterRepository->createGradingProgramGroupQueryBuilder(
            $programGroup,
            $person,
            $isManager,
            $q
        );
        assert($queryBuilder instanceof QueryBuilder);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $breadcrumb = [
            [
                'fixed' => $translator->trans('title.group_list', [], 'itp_grading'),
                'routeName' => 'in_company_training_phase_tracking_grading_group_list',
                'routeParams' => ['academicYear' => $programGroup->getGroup()->getGrade()->getTraining()->getAcademicYear()->getId()]
            ],
            ['fixed' => $programGroup->getGroup()->__toString()]
        ];

        $title = $translator->trans('title.group_student_list', [], 'itp_grading') . ' - ' . $programGroup->getGroup()->__toString();

        return $this->render('itp/training_program/grading/list.html.twig', [
            'menu_path' => 'in_company_training_phase_tracking_grading_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'url_path' => '',
            'report_path' => 'in_company_training_phase_tracking_grading_evaluation_report',
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_tracking'
        ]);
    }

    #[Route(path: '/evaluacion/informe/{studentProgramWorkcenter}', name: 'evaluation_report', requirements: ['studentProgramWorkcenter' => '\d+'], methods: ['GET'])]
    final public function evaluationReport(
        TranslatorInterface                        $translator,
        Environment                                $engine,
        PerformanceScaleValueRepository            $performanceScaleValueRepository,
        CriterionRepository                        $criterionRepository,
        StudentProgramWorkcenterActivityRepository $studentProgramWorkcenterActivityRepository,
        ActivityRepository                         $activityRepository,
        StudentProgramWorkcenter                   $studentProgramWorkcenter
    ): Response {
        $this->denyAccessUnlessGranted(StudentProgramWorkcenterVoter::VIEW_EVALUATION, $studentProgramWorkcenter);

        $academicYear = $studentProgramWorkcenter
            ->getStudentProgram()?->getStudentEnrollment()?->getGroup()?->getGrade()?->getTraining()?->getAcademicYear();
        assert($academicYear instanceof AcademicYear);

        // Pre-caching
        $activities = $activityRepository->findByStudentProgramWorkcenter($studentProgramWorkcenter);
        $studentProgramWorkcenterActivityRepository->findByStudentProgramWorkcenter($studentProgramWorkcenter);

        $stats = $criterionRepository->getStudentProgramWorkcenterStats($studentProgramWorkcenter);

        $title = $translator->trans('title.evaluation_report', [], 'itp_grading')
            . ' - ' . $studentProgramWorkcenter->__toString();

        $fileName = $title . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        assert($mpdf instanceof Mpdf);
        $tmp = '';

        try {
            $template = $studentProgramWorkcenter->getStudentProgram()?->getProgramGroup()?->getProgramGrade()?->getTrainingProgram()?->getFinalReportTemplate() ??
                $studentProgramWorkcenter->getStudentProgram()
                    ?->getStudentEnrollment()?->getGroup()?->getGrade()?->getTraining()?->getAcademicYear()?->getDefaultPortraitTemplate();

            if ($template instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }

            $mpdf->SetFont('DejaVuSansCondensed');
            $mpdf->SetFontSize(9);

            $mpdf->WriteHTML($engine->render('itp/training_program/grading/evaluation_report.html.twig', [
                'student_program_workcenter' => $studentProgramWorkcenter,
                'academic_year' => $academicYear,
                'stats' => $stats,
                'activities' => $activities
            ]));

            $mpdf->SetTitle($title);

            $response = new Response();
            $response->headers->set('Content-Type', 'application/pdf');
            $response->setContent($mpdf->Output($fileName, Destination::STRING_RETURN));

            $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

            return $response;
        } finally {
            if ($tmp) {
                unlink($tmp);
            }
        }
    }
}
