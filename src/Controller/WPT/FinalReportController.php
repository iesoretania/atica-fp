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
use App\Entity\WPT\AgreementEnrollment;
use App\Entity\WPT\Report;
use App\Form\Type\WPT\FinalReportType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WPT\WorkDayRepository;
use App\Repository\WPT\WPTGroupRepository;
use App\Security\OrganizationVoter;
use App\Security\WPT\AgreementEnrollmentVoter;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;

/**
 * @Route("/fct/informe")
 */
class FinalReportController extends AbstractController
{
    /**
     * @Route("/acuerdo/{academicYear}/{page}", name="workplace_training_final_report_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        WPTGroupRepository $groupRepository,
        TeacherRepository $teacherRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_FILL_REPORT, $organization);

        $q = $request->get('q');
        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $person = $this->getUser();
        $maxPerPage = $this->getParameter('page.size');

        $pager = TrackingController::generateAgreementPaginator(
            $groupRepository,
            $teacherRepository,
            $academicYear,
            $queryBuilder,
            $person,
            $isManager,
            $q,
            $page,
            $maxPerPage
        );

        $title = $translator->trans('title.agreement.list', [], 'wpt_final_report');

        return $this->render('wpt/final_report/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_final_report',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/cumplimentar/{id}", name="workplace_training_final_report_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        TranslatorInterface $translator,
        AgreementEnrollment $agreementEnrollment
    ) {
        $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::VIEW_REPORT, $agreementEnrollment);

        $readOnly = !$this->isGranted(AgreementEnrollmentVoter::FILL_REPORT, $agreementEnrollment);

        $em = $this->getDoctrine()->getManager();

        $report = $agreementEnrollment->getReport();

        if (null === $report) {
            $report = new Report();
            $report
                ->setAgreementEnrollment($agreementEnrollment)
                ->setSignDate(new \DateTime());
            $em->persist($report);
        }

        $form = $this->createForm(FinalReportType::class, $report, [
            'disabled' => $readOnly
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_final_report'));
                return $this->redirectToRoute('workplace_training_final_report_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_final_report'));
            }
        }

        $title = $translator->trans(
            'title.report.fill',
            [],
            'wpt_final_report'
        );

        $breadcrumb = [
            ['fixed' => (string) $agreementEnrollment],
            ['fixed' => $title]
        ];

        return $this->render('wpt/final_report/form.html.twig', [
            'menu_path' => 'workplace_training_final_report_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }
    /**
     * @Route("/descargar/{id}", name="workplace_training_final_report_report",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function generateAction(
        TranslatorInterface $translator,
        WorkDayRepository $workDayRepository,
        AgreementEnrollment $agreementEnrollment
    ) {
        $this->denyAccessUnlessGranted(AgreementEnrollmentVoter::VIEW_REPORT, $agreementEnrollment);
        if (null === $agreementEnrollment->getReport()) {
            throw $this->createNotFoundException();
        }

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4']]);
        $tmp = '';

        try {
            $template = $agreementEnrollment->getAgreement()->getShift()->getFinalReportTemplate();
            if ($template) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }
            $mpdf->SetFont('DejaVuSansCondensed');
            $mpdf->SetFontSize(9);
            $mpdf->AddPage();
            $mpdf->WriteText(40, 40.8, (string) $agreementEnrollment->getStudentEnrollment()->getPerson());
            $mpdf->WriteText(40, 46.6, (string) $agreementEnrollment->getAgreement()->getShift()
                ->getGrade()->getTraining()->getAcademicYear()->getOrganization());
            $mpdf->WriteText(40, 53, (string) $agreementEnrollment->getStudentEnrollment()->getGroup()
                ->getGrade()->getTraining());
            $mpdf->WriteText(179, 53, (string) $agreementEnrollment->getAgreement()->getShift()->getType());
            $mpdf->WriteText(40, 59.1, (string) $agreementEnrollment->getAgreement()->getWorkcenter());
            $mpdf->WriteText(165, 59.1, (string) $workDayRepository->getAgreementTrackedHours($agreementEnrollment->getAgreement()));
            $mpdf->WriteText(82, 65.1, (string) $agreementEnrollment->getWorkTutor());
            $mpdf->WriteText(68, 71.5, (string) $agreementEnrollment->getEducationalTutor());

            $mpdf->WriteText(108 + $agreementEnrollment->getReport()->getProfessionalCompetence() * 35.0, 137, 'X');
            $mpdf->WriteText(108 + $agreementEnrollment->getReport()->getOrganizationalCompetence() * 35.0, 143.5, 'X');
            $mpdf->WriteText(108 + $agreementEnrollment->getReport()->getRelationalCompetence() * 35.0, 149.5, 'X');
            $mpdf->WriteText(108 + $agreementEnrollment->getReport()->getContingencyResponse() * 35.0, 155.5, 'X');

            $mpdf->WriteText(104.6, 247.6, $agreementEnrollment->getReport()->getSignDate()->format('d'));
            $mpdf->WriteText(154.4, 247.6, $agreementEnrollment->getReport()->getSignDate()->format('y'));
            $mpdf->WriteText(89, 275.6, (string) $agreementEnrollment->getWorkTutor());

            TrackingCalendarController::pdfWriteFixedPosHTML(
                $mpdf,
                $agreementEnrollment->getAgreement()->getWorkcenter()->getCity(),
                61,
                244.4,
                38,
                5,
                'auto',
                'center'
            );
            TrackingCalendarController::pdfWriteFixedPosHTML(
                $mpdf,
                $translator->trans('r_month'
                    . ($agreementEnrollment->getReport()->getSignDate()->format('n') - 1), [], 'calendar'),
                116,
                244.4,
                26,
                5,
                'auto',
                'center'
            );
            TrackingCalendarController::pdfWriteFixedPosHTML(
                $mpdf,
                $agreementEnrollment->getReport()->getWorkActivities(),
                18,
                80,
                179,
                40.5,
                'auto',
                'justify'
            );
            TrackingCalendarController::pdfWriteFixedPosHTML(
                $mpdf,
                $agreementEnrollment->getReport()->getProposedChanges(),
                18,
                195,
                179,
                43,
                'auto',
                'justify'
            );

            $title = $translator->trans('title.report', [], 'wpt_final_report') . ' - '
                . $agreementEnrollment->getStudentEnrollment() . ' - '
                . $agreementEnrollment->getAgreement()->getWorkcenter();

            $fileName = $title . '.pdf';

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
