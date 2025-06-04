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
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Entity\Survey;
use App\Repository\AnsweredSurveyQuestionRepository;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\ItpModule\EducationalTutorAnsweredSurveyRepository;
use App\Repository\ItpModule\StudentAnsweredSurveyRepository;
use App\Repository\ItpModule\TeacherRepository;
use App\Repository\ItpModule\TrainingProgramRepository;
use App\Repository\ItpModule\WorkTutorAnsweredSurveyRepository;
use App\Repository\SurveyQuestionRepository;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TrainingProgramVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
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

#[Route(path: '/formacion/informe', name: 'in_company_training_phase_report')]
class ReportController extends AbstractController
{
    #[Route(path: '/', name: '', methods: ['GET'])]
    public function index(UserExtensionService $userExtensionService): Response
    {
        $this->denyAccessUnlessGranted(
            ItpOrganizationVoter::ITP_MANAGER,
            $userExtensionService->getCurrentOrganization()
        );
        return $this->render(
            'default/index.html.twig',
            [
                'menu' => true
            ]
        );
    }

    private function genericList(
        Request                  $request,
        UserExtensionService     $userExtensionService,
        TranslatorInterface      $translator,
        AcademicYearRepository   $academicYearRepository,
        TrainingProgramRepository $trainingProgramRepository,
        string                   $title,
        string                   $routeName,
        AcademicYear             $academicYear = null,
        int                      $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_MANAGER, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $person = $this->getUser();
        assert($person instanceof Person);

        $q = $request->get('q');

        $queryBuilder = $trainingProgramRepository->createByManagerQueryBuilder(
            $academicYear,
            $isManager,
            $person,
            $request->get('q')
        );

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans($title, [], 'itp_report');

        return $this->render('itp/report/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_training_program',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization),
            'route_name' => $routeName
        ]);
    }

    #[Route(path: '/encuesta/estudiantes/listar/{academicYear}/{page}', name: '_student_survey_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function studentList(
        Request                $request,
        UserExtensionService   $userExtensionService,
        TranslatorInterface    $translator,
        AcademicYearRepository $academicYearRepository,
        TrainingProgramRepository $trainingProgramRepository,
        AcademicYear           $academicYear = null,
        int                    $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $trainingProgramRepository,
            'title.student_survey',
            'in_company_training_phase_report_student_survey_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/encuesta/empresas/listar/{academicYear}/{page}', name: '_work_tutor_survey_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function workTutorList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        TrainingProgramRepository $trainingProgramRepository,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $trainingProgramRepository,
            'title.work_tutor_survey',
            'in_company_training_phase_report_work_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/encuesta/centro/listar/{academicYear}/{page}', name: '_educational_tutor_survey_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function educationalTutorList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        TrainingProgramRepository $trainingProgramRepository,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response
    {
        return $this->genericList(
            $request,
            $userExtensionService,
            $translator,
            $academicYearRepository,
            $trainingProgramRepository,
            'title.educational_tutor_survey',
            'in_company_training_phase_report_educational_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    #[Route(path: '/encuesta/estudiantes/{trainingProgram}/{academicYear}', name: '_student_survey_report', requirements: ['trainingProgram' => '\d+', 'academicYear' => '\d+'], methods: ['GET'])]
    public function studentsReport(
        TranslatorInterface $translator,
        Environment $engine,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        TrainingProgram $trainingProgram,
        AcademicYear $academicYear
    ): Response {
        $this->denyAccessUnlessGranted(TrainingProgramVoter::REPORT_STUDENT_SURVEY, $trainingProgram);

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $studentEnrollmentStats = $studentAnsweredSurveyRepository
            ->getStatsByTrainingProgramAndAcademicYear($trainingProgram, $academicYear);

        $stats = [];
        $studentAnswers = [];

        $survey = $trainingProgram->getStudentSurvey();

        if ($survey instanceof Survey) {
            $studentAnswers = $studentAnsweredSurveyRepository->findByTrainingProgramAndAcademicYear($trainingProgram, $academicYear);

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
            return $this->render('itp/report/no_survey.html.twig', [
                'menu_path' => 'in_company_training_phase_report_student_survey_list'
            ]);
        }

        $organization = $academicYear->getOrganization();

        $html = $engine->render('itp/report/student_survey_report.html.twig', [
            'student_enrollment_stats' => $studentEnrollmentStats,
            'academic_year' => $academicYear,
            'training_program' => $trainingProgram,
            'organization' => $organization,
            'stats' => $stats,
            'student_answered_surveys' => $studentAnswers
        ]);

        $fileName = $translator->trans('title.student_survey', [], 'itp_report')
            . ' - '
            . $trainingProgram->getName() . ' (' . $academicYear->getDescription() . ').pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    #[Route(path: '/encuesta/empresas/{trainingProgram}/{academicYear}', name: '_work_tutor_survey_report', requirements: ['trainingProgram' => '\d+', 'academicYear' => '\d'], methods: ['GET'])]
    public function workTutorReport(
        TranslatorInterface               $translator,
        Environment                       $engine,
        SurveyQuestionRepository          $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository  $answeredSurveyQuestionRepository,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        TrainingProgram                   $trainingProgram,
        AcademicYear                      $academicYear
    ): Response {
        $this->denyAccessUnlessGranted(TrainingProgramVoter::REPORT_WORK_TUTOR_SURVEY, $trainingProgram);

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $workTutorStats = $workTutorAnsweredSurveyRepository
            ->getStatsByTrainingProgramAndAcademicYear($trainingProgram, $academicYear);

        $stats = [];
        $workTutorAnswers = [];

        $survey = $trainingProgram->getStudentSurvey();

        if ($survey instanceof Survey) {
            $workTutorAnswers = $workTutorAnsweredSurveyRepository
                ->findByTrainingProgramAndAcademicYear($trainingProgram, $academicYear);

            $list = [];
            foreach ($workTutorAnswers as $workTutorAnswer) {
                $list[] = $workTutorAnswer->getAnsweredSurvey();
            }

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        if ($stats === []) {
            return $this->render('wpt/report/no_survey.html.twig', [
                'menu_path' => 'in_company_training_phase_report_work_tutor_survey_list'
            ]);
        }

        $organization = $academicYear->getOrganization();

        $html = $engine->render('itp/report/work_tutor_survey_report.html.twig', [
            'work_tutor_stats' => $workTutorStats,
            'training_program' => $trainingProgram,
            'organization' => $organization,
            'academic_year' => $academicYear,
            'stats' => $stats,
            'work_tutor_surveys' => $workTutorAnswers
        ]);

        $fileName = $translator->trans('title.work_tutor_survey', [], 'itp_report')
            . ' - '
            . $trainingProgram->getName() . ' (' . $academicYear->getDescription() . ').pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    #[Route(path: '/encuesta/centro/{trainingProgram}/{academicYear}', name: '_educational_tutor_survey_report', requirements: ['trainingProgram' => '\d+', 'academicYear' => '\d+'], methods: ['GET'])]
    public function educationalTutorReport(
        TranslatorInterface                      $translator,
        Environment                              $engine,
        SurveyQuestionRepository                 $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository         $answeredSurveyQuestionRepository,
        EducationalTutorAnsweredSurveyRepository $educationalTutorAnsweredSurveyRepository,
        TeacherRepository                        $itpTeacherRepository,
        TrainingProgram                          $trainingProgram,
        AcademicYear                             $academicYear
    ): Response {
        $this->denyAccessUnlessGranted(TrainingProgramVoter::REPORT_EDUCATIONAL_TUTOR_SURVEY, $trainingProgram);

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $educationalTutorStats = $educationalTutorAnsweredSurveyRepository
            ->getStatsByTrainingProgramAndAcademicYear($trainingProgram, $academicYear);

        $stats = [];
        $educationalTutorAnswers = [];

        $survey = $trainingProgram->getEducationalTutorSurvey();

        if ($survey instanceof Survey) {
            $educationalTutorAnswers = $educationalTutorAnsweredSurveyRepository
                ->findByTrainingProgramAndAcademicYear($trainingProgram, $academicYear);

            $list = [];
            foreach ($educationalTutorAnswers as $workTutorAnswer) {
                $list[] = $workTutorAnswer->getAnsweredSurvey();
            }

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        if ($stats === []) {
            return $this->render('wpt/report/no_survey.html.twig', [
                'menu_path' => 'workplace_training_report_educational_tutor_survey_list'
            ]);
        }

        $organization = $academicYear->getOrganization();

        $html = $engine->render('itp/report/educational_tutor_survey_report.twig', [
            'educational_tutor_stats' => $educationalTutorStats,
            'training_program' => $trainingProgram,
            'organization' => $organization,
            'academic_year' => $academicYear,
            'stats' => $stats,
            'educational_tutor_surveys' => $educationalTutorAnswers
        ]);

        $fileName = $translator->trans('title.educational_tutor_survey', [], 'wpt_report')
            . ' - ' . $organization->getName() . ' - '
            . $trainingProgram->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
}
