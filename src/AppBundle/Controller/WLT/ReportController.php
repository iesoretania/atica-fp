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

namespace AppBundle\Controller\WLT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\WLT\Project;
use AppBundle\Repository\AnsweredSurveyQuestionRepository;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\StudentEnrollmentRepository;
use AppBundle\Repository\Edu\SubjectRepository;
use AppBundle\Repository\SurveyQuestionRepository;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Repository\WLT\AgreementRepository;
use AppBundle\Repository\WLT\MeetingRepository;
use AppBundle\Repository\WLT\WLTAnsweredSurveyRepository;
use AppBundle\Repository\WLT\WLTTeacherRepository;
use AppBundle\Repository\WLT\WorkDayRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WLT\ProjectVoter;
use AppBundle\Security\WLT\WLTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

/**
 * @Route("/dual/informe")
 */
class ReportController extends Controller
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
     * @Route("/encuesta/estudiantes/listar/{academicYear}/{page}", name="work_linked_training_report_student_survey_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function studentListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $academicYearRepository,
            'title.student_survey',
            'work_linked_training_report_student_survey_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/encuesta/empresas/listar/{academicYear}/{page}", name="work_linked_training_report_company_survey_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function companyListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $academicYearRepository,
            'title.company_survey',
            'work_linked_training_report_company_survey_report',
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
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $academicYearRepository,
            'title.educational_tutor_survey',
            'work_linked_training_report_educational_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/reuniones/listar/{academicYear}/{page}", name="work_linked_training_report_meeting_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function meetingListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
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
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
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
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        return $this->genericListAction(
            $request,
            $userExtensionService,
            $academicYearRepository,
            'title.grading',
            'work_linked_training_report_grading_report',
            $academicYear,
            $page
        );
    }

    private function genericListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        $title,
        $routeName,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_MANAGE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('p')
            ->distinct(true)
            ->from(Project::class, 'p')
            ->leftJoin('p.manager', 'm')
            ->join('p.groups', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('p.name');

        $q = $request->get('q', null);
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
                ->setParameter('manager', $this->getUser()->getPerson());
        }

        $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $this->get('translator')->trans($title, [], 'wlt_report');

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
     * @Route("/encuesta/estudiantes/{id}", name="work_linked_training_report_student_survey_report",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function studentsReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WLTAnsweredSurveyRepository $wltAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_STUDENT_SURVEY, $project);

        $mpdfService = new MpdfService();

        $agreements = $project->getAgreements();

        $stats = [];

        $survey = $project->getStudentSurvey();

        if ($survey) {
            $list = $wltAnsweredSurveyRepository->findByStudentSurveyAndProject($project);

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        if (empty($stats)) {
            return $this->render('wlt/report/no_survey.html.twig');
        }

        $html = $engine->render('wlt/report/student_survey_report.html.twig', [
            'agreements' => $agreements,
            'project' => $project,
            'stats' => $stats
        ]);

        $fileName = $translator->trans('title.student_survey', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/encuesta/empresas/{id}", name="work_linked_training_report_company_survey_report",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function companyReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WLTAnsweredSurveyRepository $wltAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_COMPANY_SURVEY, $project);

        $mpdfService = new MpdfService();

        $agreements = $project->getAgreements();

        $stats = [];

        $survey = $project->getStudentSurvey();

        if ($survey) {
            $list = $wltAnsweredSurveyRepository->findByCompanySurveyAndProject($project);

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        if (empty($stats)) {
            return $this->render('wlt/report/no_survey.html.twig');
        }

        $html = $engine->render('wlt/report/company_survey_report.html.twig', [
            'agreements' => $agreements,
            'project' => $project,
            'stats' => $stats
        ]);

        $fileName = $translator->trans('title.company_survey', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/encuesta/centro/{id}", name="work_linked_training_report_educational_tutor_survey_report",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function educationalTutorReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WLTAnsweredSurveyRepository $wltAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        WLTTeacherRepository $wltTeacherRepository,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_ORGANIZATION_SURVEY, $project);

        $mpdfService = new MpdfService();

        $stats = [];

        $survey = $project->getEducationalTutorSurvey();

        if ($survey) {
            $list = $wltAnsweredSurveyRepository->findByEducationalTutorSurveyAndProject($project);

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        $teachers = $wltTeacherRepository->findByEducationalTutorProjectWithAnsweredSurvey($project);


        if (empty($stats)) {
            return $this->render('wlt/report/no_survey.html.twig');
        }

        $html = $engine->render('wlt/report/educational_tutor_survey_report.twig', [
            'teachers' => $teachers,
            'project' => $project,
            'stats' => $stats
        ]);

        $fileName = $translator->trans('title.educational_tutor_survey', [], 'wlt_report')
            . ' - ' . $project->getOrganization() . ' - '
            . $project->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/reuniones/{id}", name="work_linked_training_report_meeting_report",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function meetingReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        WLTTeacherRepository $wltTeacherRepository,
        AgreementRepository $agreementRepository,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        MeetingRepository $meetingRepository,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_MEETING, $project);

        $teachers = $wltTeacherRepository->findByProject($project);

        $teacherStats = [];

        foreach ($teachers as $teacher) {
            $teacherStats[] = [$teacher, $agreementRepository->meetingStatsByTeacher($teacher)];
        }

        $studentEnrollments = $project->getStudentEnrollments();

        $studentData = [];

        foreach ($studentEnrollments as $studentEnrollment) {
            $studentData[] = [
                $studentEnrollment,
                $meetingRepository->findByStudentEnrollmentAndProject($studentEnrollment, $project)
            ];
        }

        $html = $engine->render('wlt/report/meeting_report.html.twig', [
            'project' => $project,
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
     * @Route("/evaluacion/{id}", name="work_linked_training_report_grading_report",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function gradingReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        SubjectRepository $subjectRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_GRADING, $project);

        $studentEnrollments = $project->getStudentEnrollments();

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

            $studentData[] = [$studentEnrollment, $report];
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
     * @Route("/asistencia/{id}", name="work_linked_training_report_attendance_report",
     *     requirements={"id" = "\d+"}, methods={"GET"})
     */
    public function attendanceReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WorkDayRepository $workDayRepository,
        AgreementRepository $agreementRepository,
        Project $project
    ) {
        $this->denyAccessUnlessGranted(ProjectVoter::REPORT_ATTENDANCE, $project);

        $agreementData = $agreementRepository->attendanceStatsByProject($project);

        $studentEnrollments = $project->getStudentEnrollments();
        $studentData = [];

        /** @var StudentEnrollment $studentEnrollment */
        foreach ($studentEnrollments as $studentEnrollment) {
            $workDays = $workDayRepository->findByStudentEnrollment($studentEnrollment);

            $studentData[] = [$studentEnrollment, $workDays];
        }

        $html = $engine->render('wlt/report/attendance_report.html.twig', [
            'project' => $project,
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
}
