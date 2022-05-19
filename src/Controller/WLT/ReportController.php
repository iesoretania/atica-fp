<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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
use App\Entity\Edu\StudentEnrollment;
use App\Entity\WLT\Project;
use App\Repository\AnsweredSurveyQuestionRepository;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\SubjectRepository;
use App\Repository\SurveyQuestionRepository;
use App\Repository\WLT\ActivityRealizationRepository;
use App\Repository\WLT\AgreementRepository;
use App\Repository\WLT\LearningProgramRepository;
use App\Repository\WLT\MeetingRepository;
use App\Repository\WLT\StudentAnsweredSurveyRepository;
use App\Repository\WLT\WLTAnsweredSurveyRepository;
use App\Repository\WLT\WLTStudentEnrollmentRepository;
use App\Repository\WLT\WLTTeacherRepository;
use App\Repository\WLT\WorkDayRepository;
use App\Repository\WLT\WorkTutorAnsweredSurveyRepository;
use App\Security\OrganizationVoter;
use App\Security\WLT\ProjectVoter;
use App\Security\WLT\WLTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

/**
 * @Route("/dual/informe")
 */
class ReportController extends AbstractController
{
    /**
     * @Route("/", name="work_linked_training_report", methods={"GET"})
     */
    public function indexAction(UserExtensionService $userExtensionService)
    {
        $this->denyAccessUnlessGranted(
            WLTOrganizationVoter::WLT_MANAGER,
            $userExtensionService->getCurrentOrganization()
        );
        return $this->render(
            'default/index.html.twig',
            [
                'menu' => true
            ]
        );
    }

    /**
     * @Route("/reuniones/listar/{academicYear}/{page}", name="work_linked_training_report_meeting_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function meetingListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        int $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            'title.meeting',
            'work_linked_training_report_meeting_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/asistencia/listar/{academicYear}/{page}", name="work_linked_training_report_attendance_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function attendanceListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        int $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            'title.attendance',
            'work_linked_training_report_attendance_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/evaluacion/listar/{academicYear}/{page}", name="work_linked_training_report_grading_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function gradingListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        int $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            'title.grading',
            'work_linked_training_report_grading_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/programa_formativo/listar/{academicYear}/{page}", name="work_linked_training_report_learning_program_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function learningProgramListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        int $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            'title.learning_program',
            'work_linked_training_report_learning_program_report',
            $academicYear,
            $page
        );
    }

    private function genericListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        $title,
        $routeName,
        AcademicYear $academicYear = null,
        int $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

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
                ->orWhere('m.first_name LIKE :tq')
                ->orWhere('m.last_name LIKE :tq')
                ->orWhere('m.unique_identifier LIKE :tq')
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
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans($title, [], 'wlt_report');

        return $this->render('wlt/report/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_project',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization),
            'route_name' => $routeName
        ]);
    }

    /**
     * @Route("/reuniones/{project}/{academicYear}", name="work_linked_training_report_meeting_report",
     *     requirements={"project" = "\d+", "academicYear" = "\d+"}, methods={"GET"})
     */
    public function meetingReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        WLTTeacherRepository $wltTeacherRepository,
        AgreementRepository $agreementRepository,
        WLTStudentEnrollmentRepository $wltStudentEnrollmentRepository,
        MeetingRepository $meetingRepository,
        Project $project,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_MEETING, $project);

        $teachers = $wltTeacherRepository->findByProjectAndAcademicYear($project, $academicYear);

        $teacherStats = [];

        foreach ($teachers as $teacher) {
            $teacherStats[] = [
                $teacher,
                $agreementRepository->meetingStatsByTeacherAndProject($teacher, $project)
            ];
        }

        $studentEnrollments = $wltStudentEnrollmentRepository->findByProjectAndAcademicYear(
            $project,
            $academicYear
        );

        $studentData = [];

        foreach ($studentEnrollments as $studentEnrollment) {
            $studentData[] = [
                $studentEnrollment,
                $meetingRepository->findByStudentEnrollment($studentEnrollment)
            ];
        }

        $html = $engine->render('wlt/report/meeting_report.html.twig', [
            'project' => $project,
            'academic_year' => $academicYear,
            'teacher_stats' => $teacherStats,
            'student_data' => $studentData
        ]);

        $fileName = $translator->trans('title.meeting', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $mpdfService = new MpdfService();
        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/evaluacion/{project}/{academicYear}", name="work_linked_training_report_grading_report",
     *     requirements={"project" = "\d+", "academicYear" = "\d+"}, methods={"GET"})
     */
    public function gradingReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        SubjectRepository $subjectRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        WLTStudentEnrollmentRepository $wltStudentEnrollmentRepository,
        AgreementRepository $agreementRepository,
        Project $project,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_GRADING, $project);

        $studentEnrollments = $wltStudentEnrollmentRepository->findByProjectAndAcademicYear($project, $academicYear);

        $studentData = [];

        /** @var StudentEnrollment $studentEnrollment */
        foreach ($studentEnrollments as $studentEnrollment) {
            $subjects = $subjectRepository->findByGroupAndPerson($studentEnrollment->getGroup());

            $report = [];

            // precaching
            $activityRealizationRepository->findByStudentEnrollment($studentEnrollment);

            foreach ($subjects as $subject) {
                $item = [];
                $item[0] = $subject;
                $item[1] = $activityRealizationRepository->
                    reportByStudentEnrollmentAndSubject($studentEnrollment, $subject);

                $report[] = $item;
            }

            // Recopilar los comentarios de las empresas
            $agreements = $agreementRepository->findByStudentEnrollment($studentEnrollment);

            $reportRemarks = [];
            foreach ($agreements as $agreement) {
                if ($agreement->getWorkTutorRemarks()) {
                    $reportRemarks[] = [$agreement->getWorkcenter(), $agreement->getWorkTutorRemarks()];
                }
            }

            $studentData[] = [$studentEnrollment, $report, $reportRemarks];
        }

        $html = $engine->render('wlt/report/grading_report.html.twig', [
            'project' => $project,
            'student_data' => $studentData
        ]);

        $fileName = $translator->trans('title.grading', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $mpdfService = new MpdfService();
        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/asistencia/{project}/{academicYear}", name="work_linked_training_report_attendance_report",
     *     requirements={"project" = "\d+", "academicYear" = "\d+"}, methods={"GET"})
     */
    public function attendanceReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        WLTStudentEnrollmentRepository $wltStudentEnrollmentRepository,
        Project $project,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_ATTENDANCE, $project);

        $agreementData = $agreementRepository->attendanceStatsByProjectAndAcademicYear($project, $academicYear);

        $studentEnrollments = $wltStudentEnrollmentRepository->findByProjectAndAcademicYear($project, $academicYear);
        $studentData = [];

        /** @var StudentEnrollment $studentEnrollment */
        foreach ($studentEnrollments as $studentEnrollment) {
            $workDays = $workDayRepository->findByStudentEnrollment($studentEnrollment);

            $studentData[] = [$studentEnrollment, $workDays];
        }

        $html = $engine->render('wlt/report/attendance_report.html.twig', [
            'project' => $project,
            'academic_year' => $academicYear,
            'agreement_data' => $agreementData,
            'student_data' => $studentData
        ]);

        $fileName = $translator->trans('title.attendance', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $mpdfService = new MpdfService();
        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/programa_formativo/{project}/{academicYear}", name="work_linked_training_report_learning_program_report",
     *     requirements={"project" = "\d+", "academicYear" = "\d+"}, methods={"GET"})
     */
    public function learningProgramReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        LearningProgramRepository $wltLearningProgramRepository,
        Project $project,
        AcademicYear $academicYear
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_ATTENDANCE, $project);

        $learningPrograms = $wltLearningProgramRepository->findByProject($project);

        $html = $engine->render('wlt/report/learning_program_report.html.twig', [
            'project' => $project,
            'learning_programs' => $learningPrograms
        ]);

        $fileName = $translator->trans('title.learning_program', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
    /**
     * @Route("/encuesta/estudiantes/listar/{academicYear}/{page}", name="work_linked_training_report_student_survey_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function studentListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            'title.student_survey',
            'work_linked_training_report_student_survey_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/encuesta/empresas/listar/{academicYear}/{page}", name="work_linked_training_report_work_tutor_survey_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function companyListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            'title.company_survey',
            'work_linked_training_report_work_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/encuesta/centro/listar/{academicYear}/{page}",
     *     name="work_linked_training_report_educational_tutor_survey_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function educationalTutorListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            'title.educational_tutor_survey',
            'work_linked_training_report_educational_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/encuesta/estudiantes/{project}/{academicYear}", name="work_linked_training_report_student_survey_report",
     *     requirements={"id" = "\d+", "academicYear" = "\d+"}, methods={"GET"})
     */
    public function studentsReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        Project $project,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_STUDENT_SURVEY, $project);

        $mpdfService = new MpdfService();

        $studentEnrollmentStats = $studentAnsweredSurveyRepository
            ->getStatsByProjectAndAcademicYear($project, $academicYear);

        $stats = [];
        $studentAnswers = [];

        $survey = $project->getStudentSurvey();

        if ($survey) {
            $studentAnswers = $studentAnsweredSurveyRepository->findByProjectAndAcademicYear($project, $academicYear);

            $list = [];
            foreach ($studentAnswers as $studentAnswer) {
                $list[] = $studentAnswer->getAnsweredSurvey();
            }

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        if (empty($stats)) {
            return $this->render('wlt/report/no_survey.html.twig', [
                'menu_path' => 'work_linked_training_report_student_survey_list'
            ]);
        }

        $html = $engine->render('wlt/report/student_survey_report.html.twig', [
            'student_enrollment_stats' => $studentEnrollmentStats,
            'project' => $project,
            'academic_year' => $academicYear,
            'stats' => $stats,
            'student_answered_surveys' => $studentAnswers
        ]);

        $fileName = $translator->trans('title.student_survey', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/encuesta/empresas/{project}/{academicYear}", name="work_linked_training_report_work_tutor_survey_report",
     *     requirements={"id" = "\d+", "academicYear" = "\d+"}, methods={"GET"})
     */
    public function workTutorReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WLTAnsweredSurveyRepository $wltAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        Project $project,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_COMPANY_SURVEY, $project);

        $mpdfService = new MpdfService();

        $workTutorStats = $workTutorAnsweredSurveyRepository
            ->getStatsByProjectAndAcademicYear($project, $academicYear);

        $stats = [];
        $workTutorAnswers = [];

        $survey = $project->getStudentSurvey();

        if ($survey) {
            $workTutorAnswers = $workTutorAnsweredSurveyRepository->findByProjectAndAcademicYear($project, $academicYear);

            $list = $wltAnsweredSurveyRepository->findByWorkTutorSurveyProjectAndAcademicYear(
                $project,
                $academicYear
            );

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        if (empty($stats)) {
            return $this->render('wlt/report/no_survey.html.twig', [
                'menu_path' => 'work_linked_training_report_work_tutor_survey_list'
            ]);
        }

        $html = $engine->render('wlt/report/work_tutor_survey_report.html.twig', [
            'work_tutor_stats' => $workTutorStats,
            'project' => $project,
            'academic_year' => $academicYear,
            'stats' => $stats,
            'work_tutor_surveys' => $workTutorAnswers
        ]);

        $fileName = $translator->trans('title.company_survey', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/encuesta/centro/{project}/{academicYear}", name="work_linked_training_report_educational_tutor_survey_report",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function educationalTutorReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WLTAnsweredSurveyRepository $wltAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        WLTTeacherRepository $wltTeacherRepository,
        Project $project,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_ORGANIZATION_SURVEY, $project);

        $mpdfService = new MpdfService();

        $stats = [];

        $survey = $project->getEducationalTutorSurvey();

        if ($survey) {
            $list = $wltAnsweredSurveyRepository
                ->findByEducationalTutorSurveyProjectAndAcademicYear($project, $academicYear);

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        $teachers = $wltTeacherRepository
            ->getStatsByProjectAndAcademicYearWithAnsweredSurvey($project, $academicYear);

        if (empty($stats)) {
            return $this->render('wlt/report/no_survey.html.twig', [
                'menu_path' => 'work_linked_training_report_educational_tutor_survey_list'
            ]);
        }

        $html = $engine->render('wlt/report/educational_tutor_survey_report.twig', [
            'teachers' => $teachers,
            'project' => $project,
            'academic_year' => $academicYear,
            'stats' => $stats
        ]);

        $fileName = $translator->trans('title.educational_tutor_survey', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
}
