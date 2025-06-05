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
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\TravelExpense;
use App\Entity\Person;
use App\Form\Type\ItpModule\TravelExpenseType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Repository\ItpModule\TravelExpenseRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Security\ItpModule\TeacherVoter;
use App\Security\ItpModule\TravelExpenseVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
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

#[Route(path: '/formacion/desplazamiento', name: 'in_company_training_phase_travel_expense_')]
class TravelExpenseController extends AbstractController
{
    #[Route(path: '/nuevo/{teacher}', name: 'new', requirements: ['teacher' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request                            $request,
        TranslatorInterface                $translator,
        UserExtensionService               $userExtensionService,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        ManagerRegistry                    $managerRegistry,
        Teacher                            $teacher
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_CREATE_EXPENSE, $organization);

        $travelExpense = new TravelExpense();
        $travelExpense
            ->setTeacher($teacher)
            ->setFromDateTime(new \DateTime())
            ->setToDateTime(new \DateTime());

        $managerRegistry->getManager()->persist($travelExpense);

        return $this->index(
            $request,
            $translator,
            $userExtensionService,
            $studentProgramWorkcenterRepository,
            $managerRegistry,
            $travelExpense
        );
    }

    #[Route(path: '/detalle/{travelExpense}', name: 'edit', requirements: ['travelExpense' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request                   $request,
        TranslatorInterface       $translator,
        UserExtensionService      $userExtensionService,
        StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        ManagerRegistry           $managerRegistry,
        TravelExpense             $travelExpense
    ): Response {
        $this->denyAccessUnlessGranted(TravelExpenseVoter::ACCESS, $travelExpense);

        $organization = $userExtensionService->getCurrentOrganization();

        $academicYear = $travelExpense->getTeacher()->getAcademicYear();

        $em = $managerRegistry->getManager();

        $readOnly = !$this->isGranted(TeacherVoter::MANAGE_EXPENSE, $travelExpense->getTeacher());

        $teacher = $travelExpense->getTeacher();
        assert($teacher instanceof Teacher);

        $person = $teacher->getPerson();

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $trainingPrograms = $studentProgramWorkcenterRepository->findRelatedTrainingProgramsByAcademicYearPersonManagerAndQuery(
            $academicYear,
            $person,
            $isManager,
            null
        );

        if (count($trainingPrograms) === 0) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(TravelExpenseType::class, $travelExpense, [
            'disabled' => $readOnly,
            'training_programs' => $trainingPrograms
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_visit'));
                return $this->redirectToRoute('in_company_training_phase_travel_expense_detail_list', [
                    'id' => $teacher->getId()
                ]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_visit'));
            }
        }

        $title = $translator->trans(
            $travelExpense->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'itp_travel_expense'
        );

        $breadcrumb = [
            [
                'fixed' => (string) $teacher,
                'routeName' => 'in_company_training_phase_travel_expense_detail_list',
                'routeParams' => ['id' => $teacher->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('itp/travel_expense/form.html.twig', [
            'menu_path' => 'in_company_training_phase_travel_expense_teacher_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/listar/{page}', name: 'detail_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TravelExpenseRepository $travelExpenseRepository,
        TranslatorInterface $translator,
        Teacher $teacher,
        int $page = 1
    ): Response {

        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_EXPENSE, $organization);

        $allowNew = $this->isGranted(TeacherVoter::MANAGE_EXPENSE, $teacher);

        $q = $request->get('q');

        $qb = $travelExpenseRepository->createStatsByTeacherOrderByDateTimeFilterQueryBuilder($teacher, $q);

        $adapter = new QueryAdapter($qb, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $teacher->__toString() . ' - ' . $translator->trans('title.list', [], 'itp_travel_expense');

        $breadcrumb = [
            [
                'fixed' => $teacher->__toString(),
            ]
        ];

        return $this->render('itp/travel_expense/list.html.twig', [
            'menu_path' => 'in_company_training_phase_travel_expense_teacher_list',
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_travel_expense',
            'allow_new' => $allowNew,
            'teacher' => $teacher
        ]);
    }

    #[Route(path: '/resumen/{academicYear}/{page}', name: 'teacher_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function teacherList(
        Request                 $request,
        UserExtensionService    $userExtensionService,
        TranslatorInterface     $translator,
        TravelExpenseRepository $travelExpenseRepository,
        AcademicYearRepository  $academicYearRepository,
        AcademicYear            $academicYear = null,
        int                     $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(ItpOrganizationVoter::ITP_ACCESS_EXPENSE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization) ||
            $this->isGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $person = $this->getUser();
        assert($person instanceof Person);

        $q = $request->get('q');

        $queryBuilder = $travelExpenseRepository->createTeacherTravelExpenseStatsByPersonAndAcademicYearQueryBuilder(
            $academicYear,
            $isManager,
            $person,
            $q
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

        $title = $translator->trans('title.teacher_list', [], 'wpt_travel_expense');

        return $this->render('itp/travel_expense/teacher_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'itp_travel_expense',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar/{id}', name: 'operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function operation(
        Request $request,
        TravelExpenseRepository $travelExpenseRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Teacher $teacher
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WptOrganizationVoter::WPT_ACCESS_EXPENSE, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('in_company_training_phase_travel_expense_detail_list');
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
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wpt_travel_expense'));
            }
            return $this->redirectToRoute('in_company_training_phase_travel_expense_detail_list', ['id' => $teacher->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'wpt_travel_expense');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('itp/travel_expense/delete.html.twig', [
            'menu_path' => 'in_company_training_phase_travel_expense_teacher_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $travelExpenses
        ]);
    }

    #[Route(path: '/{id}/descargar', name: 'report', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function travelExpensesSummaryReport(
        Environment $engine,
        TranslatorInterface $translator,
        TravelExpenseRepository $travelExpenseRepository,
        Teacher $teacher
    ): Response {
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
            if ($template instanceof ReportTemplate) {
                $tmp = tempnam('.', 'tpl');
                file_put_contents($tmp, $template->getData());
                $mpdf->SetDocTemplate($tmp, true);
            }

            $title = $translator->trans('title.report', [], 'itp_travel_expense_report')
                . ' - ' . $teacher->getPerson()->__toString();

            $fileName = $title . '.pdf';

            $travelExpenses = $travelExpenseRepository->findByTeacherOrderByDateTime($teacher);

            $html = $engine->render('itp/travel_expense/travel_expense_report.html.twig', [
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
