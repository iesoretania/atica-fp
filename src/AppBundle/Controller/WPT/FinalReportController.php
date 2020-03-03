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

namespace AppBundle\Controller\WPT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\WPT\Agreement;
use AppBundle\Entity\WPT\Report;
use AppBundle\Form\Type\WPT\FinalReportType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\WPT\WPTGroupRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WPT\AgreementVoter;
use AppBundle\Security\WPT\WPTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/fct/informe")
 */
class FinalReportController extends Controller
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
        $person = $this->getUser()->getPerson();
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
        Agreement $agreement
    ) {
        $this->denyAccessUnlessGranted(AgreementVoter::VIEW_REPORT, $agreement);

        $readOnly = false === $this->isGranted(AgreementVoter::FILL_REPORT, $agreement);

        $em = $this->getDoctrine()->getManager();

        $report = $agreement->getReport();

        if (null === $report) {
            $report = new Report();
            $report
                ->setAgreement($agreement)
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
            ['fixed' => (string) $agreement],
            ['fixed' => $title]
        ];

        return $this->render('wpt/final_report/form.html.twig', [
            'menu_path' => 'workplace_training_final_report_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

}
