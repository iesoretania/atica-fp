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

namespace App\Controller\WPT;

use App\Entity\Edu\AcademicYear;
use App\Entity\WPT\Shift;
use App\Repository\AnsweredSurveyQuestionRepository;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\SurveyQuestionRepository;
use App\Repository\WPT\StudentAnsweredSurveyRepository;
use App\Repository\WPT\WorkTutorAnsweredSurveyRepository;
use App\Repository\WPT\WPTAnsweredSurveyRepository;
use App\Repository\WPT\WPTTeacherRepository;
use App\Security\OrganizationVoter;
use App\Security\WPT\ShiftVoter;
use App\Security\WPT\WPTOrganizationVoter;
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
 * @Route("/fct/informe")
 */
class ReportController extends AbstractController
{
    /**
     * @Route("/", name="workplace_training_report", methods={"GET"})
     */
    public function indexAction(UserExtensionService $userExtensionService)
    {
        $this->denyAccessUnlessGranted(
            WPTOrganizationVoter::WPT_MANAGER,
            $userExtensionService->getCurrentOrganization()
        );
        return $this->render(
            'default/index.html.twig',
            [
                'menu' => true
            ]
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

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('s')
            ->distinct()
            ->from(Shift::class, 's')
            ->join('s.subject', 'su')
            ->join('su.grade', 'gr')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('s.name');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('gr.name LIKE :tq')
                ->orWhere('sh.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        if (!$isManager) {
            $queryBuilder
                ->andWhere('(d.head IS NOT NULL AND h.person = :manager)')
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

        $title = $translator->trans($title, [], 'wpt_report');

        return $this->render('wpt/report/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_shift',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization),
            'route_name' => $routeName
        ]);
    }

    /**
     * @Route("/encuesta/estudiantes/listar/{academicYear}/{page}", name="workplace_training_report_student_survey_list",
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
            'workplace_training_report_student_survey_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/encuesta/empresas/listar/{academicYear}/{page}", name="workplace_training_report_work_tutor_survey_list",
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
            'workplace_training_report_work_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/encuesta/centro/listar/{academicYear}/{page}",
     *     name="workplace_training_report_educational_tutor_survey_list",
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
            'workplace_training_report_educational_tutor_survey_report',
            $academicYear,
            $page
        );
    }

    /**
     * @Route("/encuesta/estudiantes/{shift}", name="workplace_training_report_student_survey_report",
     *     requirements={"shift" = "\d+"}, methods={"GET"})
     */
    public function studentsReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        StudentAnsweredSurveyRepository $studentAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        Shift $shift,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ShiftVoter::REPORT_STUDENT_SURVEY, $shift);

        $mpdfService = new MpdfService();

        $studentEnrollmentStats = $studentAnsweredSurveyRepository
            ->getStatsByShift($shift);

        $stats = [];
        $studentAnswers = [];

        $survey = $shift->getStudentSurvey();

        if ($survey) {
            $studentAnswers = $studentAnsweredSurveyRepository->findByShift($shift);

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
            return $this->render('wpt/report/no_survey.html.twig', [
                'menu_path' => 'workplace_training_report_student_survey_list'
            ]);
        }

        $grade = $shift->getGrade();
        $organization = $grade->getTraining()->getAcademicYear()->getOrganization();
        $html = $engine->render('wpt/report/student_survey_report.html.twig', [
            'student_enrollment_stats' => $studentEnrollmentStats,
            'shift' => $shift,
            'grade' => $grade,
            'organization' => $organization,
            'stats' => $stats,
            'student_answered_surveys' => $studentAnswers
        ]);

        $fileName = $translator->trans('title.student_survey', [], 'wpt_report')
            . ' - ' . $organization->getName() . ' - '
            . $shift->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/encuesta/empresas/{shift}", name="workplace_training_report_work_tutor_survey_report",
     *     requirements={"shift" = "\d+"}, methods={"GET"})
     */
    public function workTutorReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WPTAnsweredSurveyRepository $wptAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        WorkTutorAnsweredSurveyRepository $workTutorAnsweredSurveyRepository,
        Shift $shift,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ShiftVoter::REPORT_COMPANY_SURVEY, $shift);

        $mpdfService = new MpdfService();

        $workTutorStats = $workTutorAnsweredSurveyRepository
            ->getStatsByShift($shift);

        $stats = [];
        $workTutorAnswers = [];

        $survey = $shift->getStudentSurvey();

        if ($survey) {
            $workTutorAnswers = $workTutorAnsweredSurveyRepository->findByShift($shift);

            $list = $wptAnsweredSurveyRepository->findByEducationalTutorSurveyShift(
                $shift
            );

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        if (empty($stats)) {
            return $this->render('wpt/report/no_survey.html.twig', [
                'menu_path' => 'workplace_training_report_work_tutor_survey_list'
            ]);
        }

        $grade = $shift->getGrade();
        $organization = $grade->getTraining()->getAcademicYear()->getOrganization();

        $html = $engine->render('wpt/report/work_tutor_survey_report.html.twig', [
            'work_tutor_stats' => $workTutorStats,
            'shift' => $shift,
            'organization' => $organization,
            'grade' => $grade,
            'stats' => $stats,
            'work_tutor_surveys' => $workTutorAnswers
        ]);

        $fileName = $translator->trans('title.company_survey', [], 'wpt_report')
            . ' - ' . $organization->getName() . ' - '
            . $shift->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    /**
     * @Route("/encuesta/centro/{shift}", name="workplace_training_report_educational_tutor_survey_report",
     *     requirements={"shift" = "\d+"}, methods={"GET"})
     */
    public function educationalTutorReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        WPTAnsweredSurveyRepository $wptAnsweredSurveyRepository,
        SurveyQuestionRepository $surveyQuestionRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository,
        WPTTeacherRepository $wptTeacherRepository,
        Shift $shift
    ) {
        $this->denyAccessUnlessGranted(ShiftVoter::REPORT_ORGANIZATION_SURVEY, $shift);

        $mpdfService = new MpdfService();

        $stats = [];

        $survey = $shift->getEducationalTutorSurvey();

        if ($survey) {
            $list = $wptAnsweredSurveyRepository
                ->findByEducationalTutorSurveyShift($shift);

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($list);

            $stats = [$surveyStats, $answers];
        }

        $teachers = $wptTeacherRepository
            ->getStatsByShiftWithAnsweredSurvey($shift);

        if (empty($stats)) {
            return $this->render('wpt/report/no_survey.html.twig', [
                'menu_path' => 'workplace_training_report_educational_tutor_survey_list'
            ]);
        }

        $grade = $shift->getGrade();
        $organization = $grade->getTraining()->getAcademicYear()->getOrganization();

        $html = $engine->render('wpt/report/educational_tutor_survey_report.twig', [
            'teachers' => $teachers,
            'shift' => $shift,
            'organization' => $organization,
            'grade' => $grade,
            'stats' => $stats
        ]);

        $fileName = $translator->trans('title.educational_tutor_survey', [], 'wpt_report')
            . ' - ' . $organization->getName() . ' - '
            . $shift->getName() . '.pdf';

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
}
