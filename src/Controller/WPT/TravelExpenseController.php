<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WPT\TravelExpense;
use App\Form\Type\WPT\TravelExpenseType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WPT\AgreementRepository;
use App\Repository\WPT\TravelExpenseRepository;
use App\Repository\WPT\WPTTeacherRepository;
use App\Security\Edu\EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WPT\TravelExpenseVoter;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Mpdf\Mpdf;
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
 * @Route("/fct/desplazamiento")
 */
class TravelExpenseController extends AbstractController
{
    /**
     * @Route("/nuevo/{id}", name="workplace_training_travel_expense_new",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        AgreementRepository $agreementRepository,
        Teacher $teacher
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_CREATE_EXPENSE, $organization);

        $travelExpense = new TravelExpense();
        $travelExpense
            ->setTeacher($teacher)
            ->setFromDateTime(new \DateTime())
            ->setToDateTime(new \DateTime());

        $this->getDoctrine()->getManager()->persist($travelExpense);

        return $this->indexAction(
            $request,
            $translator,
            $agreementRepository,
            $travelExpense
        );
    }

    /**
     * @Route("/detalle/{id}", name="workplace_training_travel_expense_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        AgreementRepository $agreementRepository,
        TravelExpense $travelExpense
    ) {
        $this->denyAccessUnlessGranted(TravelExpenseVoter::ACCESS, $travelExpense);

        $academicYear = $travelExpense->getTeacher()->getAcademicYear();

        $em = $this->getDoctrine()->getManager();

        $readOnly = !$this->isGranted(TravelExpenseVoter::MANAGE, $travelExpense);

        $teacher = $travelExpense->getTeacher();
        $agreements = $agreementRepository->findByAcademicYearAndEducationalTutorOrDepartmentHead(
            $academicYear,
            $teacher
        );

        if ((is_array($agreements) || $agreements instanceof \Countable ? count($agreements) : 0) === 0) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TravelExpenseType::class, $travelExpense, [
            'disabled' => $readOnly,
            'agreements' => $agreements
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_visit'));
                return $this->redirectToRoute('workplace_training_travel_expense_detail_list', [
                    'id' => $teacher->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_visit'));
            }
        }

        $title = $translator->trans(
            $travelExpense->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wpt_travel_expense'
        );

        $breadcrumb = [
            [
                'fixed' => (string) $teacher,
                'routeName' => 'workplace_training_travel_expense_detail_list',
                'routeParams' => ['id' => $teacher->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/travel_expense/form.html.twig', [
            'menu_path' => 'workplace_training_travel_expense_teacher_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/listar/{page}", name="workplace_training_travel_expense_detail_list",
     *     requirements={"page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Teacher $teacher,
        $page = 1
    ) {

        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_EXPENSE, $organization);

        $allowNew = $this->isGranted(WPTOrganizationVoter::WPT_CREATE_EXPENSE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('te')
            ->addSelect('tr')
            ->addSelect('COUNT(a)')
            ->distinct()
            ->from(TravelExpense::class, 'te')
            ->join('te.travelRoute', 'tr')
            ->leftJoin('te.agreements', 'a')
            ->groupBy('te')
            ->addOrderBy('te.fromDateTime', 'DESC');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('a.name LIKE :tq')
                ->orWhere('tr.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
                ->andWhere('te.teacher = :teacher')
                ->setParameter('teacher', $teacher);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $teacher . ' - ' . $translator->trans('title.list', [], 'wpt_travel_expense');

        $breadcrumb = [
            [
                'fixed' => (string) $teacher,
            ]
        ];

        return $this->render('wpt/travel_expense/list.html.twig', [
            'menu_path' => 'workplace_training_travel_expense_teacher_list',
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_travel_expense',
            'allow_new' => $allowNew,
            'teacher' => $teacher
        ]);
    }

    /**
     * @Route("/resumen/{academicYear}/{page}", name="workplace_training_travel_expense_teacher_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function teacherListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        WPTTeacherRepository $WPTTeacherRepository,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_EXPENSE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization) ||
            $this->isGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('t')
            ->addSelect('p')
            ->addSelect('COUNT(te)')
            ->addSelect('SUM(tr.distance)')
            ->addSelect('SUM(tr.verified)')
            ->addSelect('SUM(te.otherExpenses)')
            ->from(Teacher::class, 't')
            ->join('t.person', 'p')
            ->leftJoin(TravelExpense::class, 'te', 'WITH', 'te.teacher = t')
            ->leftJoin('te.travelRoute', 'tr')
            ->groupBy('t')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        /** @var Person $person */
        /** @var Person $person */
        $person = $this->getUser();
        if (!$isManager) {
            $teachers = [$WPTTeacherRepository->findOneByPersonAndAcademicYear($person, $academicYear)];
        } else {
            $teachers = $WPTTeacherRepository->findByAcademicYear($academicYear);
        }

        $queryBuilder
                ->andWhere('t IN (:teachers)')
                ->setParameter('teachers', $teachers);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.teacher_list', [], 'wpt_travel_expense');

        return $this->render('wpt/travel_expense/teacher_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_travel_expense',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/eliminar/{id}", name="workplace_training_travel_expense_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        TravelExpenseRepository $travelExpenseRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Teacher $teacher
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_EXPENSE, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute('workplace_training_travel_expense_detail_list');
        }

        $travelExpenses = $travelExpenseRepository->findAllInListById($items);
        /** @var TravelExpense $travelExpense */
        foreach ($travelExpenses as $travelExpense) {
            $this->denyAccessUnlessGranted(TravelExpenseVoter::MANAGE, $travelExpense);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                foreach ($travelExpenses as $travelExpense) {
                    $em->remove($travelExpense);
                }
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wpt_travel_expense'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wpt_travel_expense'));
            }
            return $this->redirectToRoute('workplace_training_travel_expense_detail_list', ['id' => $teacher->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'wpt_travel_expense');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wpt/travel_expense/delete.html.twig', [
            'menu_path' => 'workplace_training_travel_expense_teacher_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $travelExpenses
        ]);
    }

    /**
     * @Route("/{id}/descargar",
     *     requirements={"id" = "\d+"}, name="workplace_training_travel_expense_report", methods={"GET"})
     */
    public function travelExpensesSummaryReportAction(
        Environment $engine,
        TranslatorInterface $translator,
        TravelExpenseRepository $travelExpenseRepository,
        Teacher $teacher
    ) {
        $travelExpense = new TravelExpense();
        $travelExpense
            ->setTeacher($teacher);

        $this->denyAccessUnlessGranted(TravelExpenseVoter::ACCESS, $travelExpense);

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);
        ini_set("pcre.backtrack_limit", "5000000");

        /** @var Mpdf $mpdf */
        $mpdf = $mpdfService->getMpdf([['mode' => 'utf-8', 'format' => 'A4-L']]);
        $tmp = '';

        try {
            $template = $teacher->getAcademicYear()->getDefaultLandscapeTemplate();
            if ($template) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }

            $title = $translator->trans('title.report', [], 'wpt_travel_expense_report')
                . ' - ' . $teacher->getPerson();

            $fileName = $title . '.pdf';

            $travelExpenses = $travelExpenseRepository->findByTeacherOrderByDateTime($teacher);

            $html = $engine->render('wpt/travel_expense/travel_expense_report.html.twig', [
                'teacher' => $teacher,
                'travel_expenses' => $travelExpenses,
                'title' => $title
            ]);

            $response = $mpdfService->generatePdfResponse(
                $html,
                ['mpdf' => $mpdf]
            );
            $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

            return $response;
        } finally {
            if ($tmp) {
                unlink($tmp);
            }
        }
    }
}
