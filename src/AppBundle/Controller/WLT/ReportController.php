<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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
use AppBundle\Repository\AnsweredSurveyQuestionRepository;
use AppBundle\Repository\AnsweredSurveyRepository;
use AppBundle\Repository\Edu\StudentEnrollmentRepository;
use AppBundle\Repository\Edu\SubjectRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\Edu\TrainingRepository;
use AppBundle\Repository\SurveyQuestionRepository;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use AppBundle\Repository\WLT\AgreementRepository;
use AppBundle\Repository\WLT\MeetingRepository;
use AppBundle\Repository\WLT\WorkDayRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
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
            OrganizationVoter::WLT_MANAGER,
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
     * @Route("/encuesta/estudiantes/{academicYear}", name="work_linked_training_report_student_survey_report",
     *     defaults={"academicYear" = null}, methods={"GET"})
     */
    public function studentsReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        AgreementRepository $agreementRepository,
        AnsweredSurveyRepository $answeredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        TrainingRepository $trainingRepository,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(
            OrganizationVoter::WLT_MANAGER,
            $userExtensionService->getCurrentOrganization()
        );

        if (!$academicYear) {
            $academicYear = $userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
        }

        $mpdfService = new MpdfService();

        $agreements = $agreementRepository->findByAcademicYear($academicYear);

        $trainings = $trainingRepository->findByAcademicYearAndWLT($academicYear);

        $stats = [];

        foreach ($trainings as $training) {
            $survey = $training->getWltStudentSurvey();

            if ($survey) {
                $list = $answeredSurveyRepository->findByWltStudentSurveyAndTraining($survey, $training);

                $surveyStats = $surveyQuestionRepository
                    ->answerStatsBySurveyAndAnsweredSurveyList($survey, $list);

                $answers = $answeredSurveyQuestionRepository
                    ->notNumericAnswersBySurveyAndAnsweredSurveyList($survey, $list);

                $stats[] = [$training, $surveyStats, $answers];
            }
        }

        if (empty($stats)) {
            return $this->render('wlt/report/no_survey.html.twig');
        }

        $html = $engine->render('wlt/report/student_survey_report.html.twig', [
            'agreements' => $agreements,
            'academic_year' => $academicYear,
            'stats' => $stats
        ]);

        $fileName = $translator->trans('title.student_survey', [], 'wlt_report')
            . ' - ' . $academicYear->getOrganization() . ' - '
            . $academicYear . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/encuesta/empresas/{academicYear}", name="work_linked_training_report_company_survey_report",
     *     defaults={"academicYear" = null}, methods={"GET"})
     */
    public function companyReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        AgreementRepository $agreementRepository,
        AnsweredSurveyRepository $answeredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        TrainingRepository $trainingRepository,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(
            OrganizationVoter::WLT_MANAGER,
            $userExtensionService->getCurrentOrganization()
        );

        if (!$academicYear) {
            $academicYear = $userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();
        }

        $mpdfService = new MpdfService();

        $agreements = $agreementRepository->findByAcademicYear($academicYear);

        $trainings = $trainingRepository->findByAcademicYearAndWLT($academicYear);

        $stats = [];
        foreach ($trainings as $training) {
            $survey = $training->getWltCompanySurvey();
            if ($survey) {
                $list = $answeredSurveyRepository->findByWltCompanySurveyAndTraining($survey, $training);

                $surveyStats = $surveyQuestionRepository
                    ->answerStatsBySurveyAndAnsweredSurveyList($survey, $list);

                $answers = $answeredSurveyQuestionRepository
                    ->notNumericAnswersBySurveyAndAnsweredSurveyList($survey, $list);

                $stats[] = [$training, $surveyStats, $answers];
            }
        }

        if (empty($stats)) {
            return $this->render('wlt/report/no_survey.html.twig');
        }

        $html = $engine->render('wlt/report/company_survey_report.html.twig', [
            'agreements' => $agreements,
            'academic_year' => $academicYear,
            'stats' => $stats
        ]);

        $fileName = $translator->trans('title.company_survey', [], 'wlt_report')
            . ' - ' . $academicYear->getOrganization() . ' - '
            . $academicYear . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/encuesta/centro/{academicYear}", name="work_linked_training_report_organization_survey_report",
     *     defaults={"academicYear" = null}, methods={"GET"})
     */
    public function organizationReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        AnsweredSurveyRepository $answeredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        TeacherRepository $teacherRepository,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(
            OrganizationVoter::WLT_MANAGER,
            $organization
        );

        if (!$academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        if ($academicYear->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }

        $survey = $academicYear->getWltOrganizationSurvey();

        if (!$survey) {
            return $this->render('wlt/report/no_survey.html.twig');
        }

        $mpdfService = new MpdfService();

        $teachers = $teacherRepository->findByAcademicYearAndWLTEducationalTutor($academicYear);

        $list = $answeredSurveyRepository->findByWltOrganizationSurvey($survey, $academicYear);

        $surveyStats = $surveyQuestionRepository
            ->answerStatsBySurveyAndAnsweredSurveyList($survey, $list);

        $answers = $answeredSurveyQuestionRepository
            ->notNumericAnswersBySurveyAndAnsweredSurveyList($survey, $list);

        $stat = [null, $surveyStats, $answers];

        $html = $engine->render('wlt/report/organization_survey_report.html.twig', [
            'teachers' => $teachers,
            'academic_year' => $academicYear,
            'stat' => $stat
        ]);

        $fileName = $translator->trans('title.organization_survey', [], 'wlt_report')
            . ' - ' . $academicYear->getOrganization() . ' - '
            . $academicYear . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/reuniones/{academicYear}", name="work_linked_training_report_meeting_report",
     *     defaults={"academicYear" = null}, methods={"GET"})
     */
    public function meetingReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository,
        AgreementRepository $agreementRepository,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        MeetingRepository $meetingRepository,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(
            OrganizationVoter::WLT_MANAGER,
            $organization
        );

        if (!$academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        if ($academicYear->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }

        $teachers = $teacherRepository->findByAcademicYearAndWLT($academicYear);

        $teacherStats = [];

        foreach ($teachers as $teacher) {
            $teacherStats[] = [$teacher, $agreementRepository->meetingStatsByTeacher($teacher)];
        }

        $studentEnrollments = $studentEnrollmentRepository->findByAcademicYearAndWLT($academicYear);

        $studentData = [];

        foreach ($studentEnrollments as $studentEnrollment) {
            $studentData[] = [$studentEnrollment, $meetingRepository->findByStudentEnrollment($studentEnrollment)];
        }

        $html = $engine->render('wlt/report/meeting_report.html.twig', [
            'academic_year' => $academicYear,
            'teacher_stats' => $teacherStats,
            'student_data' => $studentData
        ]);

        $fileName = $translator->trans('title.meeting', [], 'wlt_report')
            . ' - ' . $academicYear->getOrganization() . ' - '
            . $academicYear . '.pdf';

        $mpdfService = new MpdfService();
        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/evaluacion/{academicYear}", name="work_linked_training_report_grading_report",
     *     defaults={"academicYear" = null}, methods={"GET"})
     */
    public function gradingReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        SubjectRepository $subjectRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(
            OrganizationVoter::WLT_MANAGER,
            $organization
        );

        if (!$academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        if ($academicYear->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }

        $studentEnrollments = $studentEnrollmentRepository->findByAcademicYearAndWLT($academicYear);

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
            'academic_year' => $academicYear,
            'student_data' => $studentData
        ]);

        $fileName = $translator->trans('title.grading', [], 'wlt_report')
            . ' - ' . $academicYear->getOrganization() . ' - '
            . $academicYear . '.pdf';

        $mpdfService = new MpdfService();
        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/asistencia/{academicYear}", name="work_linked_training_report_attendance_report",
     *     defaults={"academicYear" = null}, methods={"GET"})
     */
    public function attendanceReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        UserExtensionService $userExtensionService,
        StudentEnrollmentRepository $studentEnrollmentRepository,
        WorkDayRepository $workDayRepository,
        AcademicYear $academicYear = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(
            OrganizationVoter::WLT_MANAGER,
            $organization
        );

        if (!$academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        if ($academicYear->getOrganization() !== $organization) {
            throw $this->createAccessDeniedException();
        }

        $studentEnrollments = $studentEnrollmentRepository->findByAcademicYearAndWLT($academicYear);

        $studentData = [];

        /** @var StudentEnrollment $studentEnrollment */
        foreach ($studentEnrollments as $studentEnrollment) {
            $workDays = $workDayRepository->findByStudentEnrollment($studentEnrollment);

            $studentData[] = [$studentEnrollment, $workDays];
        }

        $html = $engine->render('wlt/report/attendance_report.html.twig', [
            'academic_year' => $academicYear,
            'student_data' => $studentData
        ]);

        $fileName = $translator->trans('title.attendance', [], 'wlt_report')
            . ' - ' . $academicYear->getOrganization() . ' - '
            . $academicYear . '.pdf';

        $mpdfService = new MpdfService();
        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
}
