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

namespace App\Controller\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Survey;
use App\Entity\WltModule\Project;
use App\Repository\AnsweredSurveyQuestionRepository;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\SubjectRepository;
use App\Repository\SurveyQuestionRepository;
use App\Repository\WltModule\ActivityRealizationRepository;
use App\Repository\WltModule\AgreementRepository;
use App\Repository\WltModule\LearningProgramRepository;
use App\Repository\WltModule\MeetingRepository;
use App\Repository\WltModule\StudentAnsweredSurveyRepository;
use App\Repository\WltModule\AnsweredSurveyRepository;
use App\Repository\WltModule\StudentEnrollmentRepository;
use App\Repository\WltModule\TeacherRepository;
use App\Repository\WltModule\WorkDayRepository;
use App\Repository\WltModule\WorkTutorAnsweredSurveyRepository;
use App\Security\OrganizationVoter;
use App\Security\WltModule\ProjectVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
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

#[Route(path: '/dual/informe')]
class ReportController extends AbstractController
{
    #[Route(path: '/', name: 'work_linked_training_report', methods: ['GET'])]
    public function index(UserExtensionService $userExtensionService): Response
    {
        $this->denyAccessUnlessGranted(
            WltOrganizationVoter::WLT_MANAGER,
            $userExtensionService->getCurrentOrganization()
        );
        return $this->render(
            'default/index.html.twig',
            [
                'menu' => true
            ]
        );
    }

    #[Route(path: '/reuniones/listar/{academicYear}/{page}', name: 'work_linked_training_report_meeting_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function meetingList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $managerRegistry,
            'title.meeting',
            'work_linked_training_report_meeting_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/asistencia/listar/{academicYear}/{page}', name: 'work_linked_training_report_attendance_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function attendanceList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $managerRegistry,
            'title.attendance',
            'work_linked_training_report_attendance_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/evaluacion/listar/{academicYear}/{page}', name: 'work_linked_training_report_grading_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function gradingList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $managerRegistry,
            'title.grading',
            'work_linked_training_report_grading_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/programa_formativo/listar/{academicYear}/{page}', name: 'work_linked_training_report_learning_program_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function learningProgramList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $managerRegistry,
            'title.learning_program',
            'work_linked_training_report_learning_program_report',
            $academicYear,
            $page
        );
    }

    private function genericList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        string $title,
        string $routeName,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_MANAGE, $organization);

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
        } catch (OutOfRangeCurrentPageException) {
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

    #[Route(path: '/reuniones/{project}/{academicYear}', name: 'work_linked_training_report_meeting_report', requirements: ['project' => '\d+', 'academicYear' => '\d+'], methods: ['GET'])]
    public function meetingReport(
        TranslatorInterface         $translator,
        Environment                 $engine,
        TeacherRepository           $wltTeacherRepository,
        AgreementRepository         $agreementRepository,
        StudentEnrollmentRepository $wltStudentEnrollmentRepository,
        MeetingRepository           $meetingRepository,
        Project                     $project,
        AcademicYear                $academicYear = null
    ): Response {
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
            . ' - ' . $project->getOrganization()->__toString() . ' - '
            . $project->getName() . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    #[Route(path: '/evaluacion/{project}/{academicYear}', name: 'work_linked_training_report_grading_report', requirements: ['project' => '\d+', 'academicYear' => '\d+'], methods: ['GET'])]
    public function gradingReport(
        TranslatorInterface           $translator,
        Environment                   $engine,
        SubjectRepository             $subjectRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        StudentEnrollmentRepository   $wltStudentEnrollmentRepository,
        AgreementRepository           $agreementRepository,
        Project                       $project,
        AcademicYear                  $academicYear = null
    ): Response {
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
            . ' - ' . $project->getOrganization()->__toString() . ' - '
            . $project->getName() . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    #[Route(path: '/asistencia/{project}/{academicYear}', name: 'work_linked_training_report_attendance_report', requirements: ['project' => '\d+', 'academicYear' => '\d+'], methods: ['GET'])]
    public function attendanceReport(
        TranslatorInterface         $translator,
        Environment                 $engine,
        WorkDayRepository           $workDayRepository,
        AgreementRepository         $agreementRepository,
        StudentEnrollmentRepository $wltStudentEnrollmentRepository,
        Project                     $project,
        AcademicYear                $academicYear = null
    ): Response {
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
            . ' - ' . $project->getOrganization()->__toString() . ' - '
            . $project->getName() . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    #[Route(path: '/programa_formativo/{project}/{academicYear}', name: 'work_linked_training_report_learning_program_report', requirements: ['project' => '\d+', 'academicYear' => '\d+'], methods: ['GET'])]
    public function learningProgramReport(
        TranslatorInterface $translator,
        Environment $engine,
        LearningProgramRepository $wltLearningProgramRepository,
        Project $project,
        AcademicYear $academicYear
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_ATTENDANCE, $project);

        $learningPrograms = $wltLearningProgramRepository->findByProject($project);

        $html = $engine->render('wlt/report/learning_program_report.html.twig', [
            'project' => $project,
            'learning_programs' => $learningPrograms
        ]);

        $fileName = $translator->trans('title.learning_program', [], 'wlt_report')
            . ' - ' . $project->getOrganization()->__toString() . ' - '
            . $project->getName() . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
    #[Route(path: '/encuesta/estudiantes/listar/{academicYear}/{page}', name: 'work_linked_training_report_student_survey_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function studentList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $managerRegistry,
            'title.student_survey',
            'work_linked_training_report_student_survey_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/encuesta/empresas/listar/{academicYear}/{page}', name: 'work_linked_training_report_work_tutor_survey_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function companyList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $managerRegistry,
            'title.company_survey',
            'work_linked_training_report_work_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/encuesta/centro/listar/{academicYear}/{page}', name: 'work_linked_training_report_educational_tutor_survey_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function educationalTutorList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $managerRegistry,
            'title.educational_tutor_survey',
            'work_linked_training_report_educational_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/encuesta/estudiantes/{project}/{academicYear}', name: 'work_linked_training_report_student_survey_report', requirements: ['id' => '\d+', 'academicYear' => '\d+'], methods: ['GET'])]
    public function studentsReport(
        TranslatorInterface $translator,
        Environment $engine,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        Project $project,
        AcademicYear $academicYear = null
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_STUDENT_SURVEY, $project);

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $studentEnrollmentStats = $studentAnsweredSurveyRepository
            ->getStatsByProjectAndAcademicYear($project, $academicYear);

        $stats = [];
        $studentAnswers = [];

        $survey = $project->getStudentSurvey();

        if ($survey instanceof Survey) {
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

        if ($stats === []) {
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
            . ' - ' . $project->getOrganization()->__toString() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    #[Route(path: '/encuesta/empresas/{project}/{academicYear}', name: 'work_linked_training_report_work_tutor_survey_report', requirements: ['id' => '\d+', 'academicYear' => '\d+'], methods: ['GET'])]
    public function workTutorReport(
        TranslatorInterface               $translator,
        Environment                       $engine,
        AnsweredSurveyRepository          $wltAnsweredSurveyRepository,
        SurveyQuestionRepository          $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository  $answeredSurveyQuestionRepository,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        Project                           $project,
        AcademicYear                      $academicYear = null
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_COMPANY_SURVEY, $project);

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $workTutorStats = $workTutorAnsweredSurveyRepository
            ->getStatsByProjectAndAcademicYear($project, $academicYear);

        $stats = [];
        $workTutorAnswers = [];

        $survey = $project->getStudentSurvey();

        if ($survey instanceof Survey) {
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

        if ($stats === []) {
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
            . ' - ' . $project->getOrganization()->__toString() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    #[Route(path: '/encuesta/centro/{project}/{academicYear}', name: 'work_linked_training_report_educational_tutor_survey_report', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function educationalTutorReport(
        TranslatorInterface              $translator,
        Environment                      $engine,
        AnsweredSurveyRepository         $wltAnsweredSurveyRepository,
        SurveyQuestionRepository         $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        TeacherRepository                $wltTeacherRepository,
        Project                          $project,
        AcademicYear                     $academicYear = null
    ): Response {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_ORGANIZATION_SURVEY, $project);

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $stats = [];

        $survey = $project->getEducationalTutorSurvey();

        if ($survey instanceof Survey) {
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

        if ($stats === []) {
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
            . ' - ' . $project->getOrganization()->__toString() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
}
