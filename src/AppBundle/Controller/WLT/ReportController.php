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
use AppBundle\Repository\AnsweredSurveyQuestionRepository;
use AppBundle\Repository\AnsweredSurveyRepository;
use AppBundle\Repository\Edu\TrainingRepository;
use AppBundle\Repository\SurveyQuestionRepository;
use AppBundle\Repository\WLT\AgreementRepository;
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
            $list = $answeredSurveyRepository->findByWltStudentSurveyAndTraining($survey, $training);

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($survey, $list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($survey, $list);

            $stats[] = [$training, $surveyStats, $answers];
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
            $list = $answeredSurveyRepository->findByWltCompanySurveyAndTraining($survey, $training);

            $surveyStats = $surveyQuestionRepository
                ->answerStatsBySurveyAndAnsweredSurveyList($survey, $list);

            $answers = $answeredSurveyQuestionRepository
                ->notNumericAnswersBySurveyAndAnsweredSurveyList($survey, $list);

            $stats[] = [$training, $surveyStats, $answers];
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
}
