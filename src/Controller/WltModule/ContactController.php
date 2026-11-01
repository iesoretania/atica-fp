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

namespace App\Controller\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\WltModule\Contact;
use App\Entity\Workcenter;
use App\Form\Model\WltModule\ContactEducationalTutorReport;
use App\Form\Model\WltModule\ContactWorkcenterReport;
use App\Form\Type\WltModule\ContactEducationalTutorReportType;
use App\Form\Type\WltModule\ContactType;
use App\Form\Type\WltModule\ContactWorkcenterReportType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\WltModule\ContactRepository;
use App\Repository\WltModule\GroupRepository as WltGroupRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Repository\WltModule\TeacherRepository as WltTeacherRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\ContactVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use App\Service\WltModule\ContactReportTeacherProvider;
use App\Service\WltModule\ContactTeacherProvider;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Service\MpdfService;
use Twig\Environment;

#[Route(path: '/dual/contacto')]
class ContactController extends AbstractController
{
    #[Route(path: '/nuevo', name: 'work_linked_training_contact_new', methods: ['GET', 'POST'])]
    public function new(
        Request              $request,
        TranslatorInterface  $translator,
        UserExtensionService $userExtensionService,
        ContactTeacherProvider $teacherProvider,
        WltTeacherRepository $wltTeacherRepository,
        ManagerRegistry      $managerRegistry
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_CREATE_VISIT, $organization);

        $academicYear = $organization->getCurrentAcademicYear();
        /** @var Person $person */
        $person = $this->getUser();
        $teacher = $wltTeacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        $visit = new Contact();
        $visit
            ->setDateTime(new \DateTime());

        if ($teacher) {
            $visit->setTeacher($teacher);
        }

        $managerRegistry->getManager()->persist($visit);

        return $this->index(
            $request,
            $translator,
            $userExtensionService,
            $teacherProvider,
            $managerRegistry,
            $visit
        );
    }

    #[Route(path: '/{id}', name: 'work_linked_training_contact_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request              $request,
        TranslatorInterface  $translator,
        UserExtensionService $userExtensionService,
        ContactTeacherProvider $teacherProvider,
        ManagerRegistry      $managerRegistry,
        Contact              $visit
    ): Response {
        $this->denyAccessUnlessGranted(ContactVoter::ACCESS, $visit);

        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $visit->getTeacher() instanceof Teacher
            ? $visit->getTeacher()->getAcademicYear()
            : $organization->getCurrentAcademicYear();

        $em = $managerRegistry->getManager();

        $readOnly = !$this->isGranted(ContactVoter::MANAGE, $visit);

        $form = $this->createForm(ContactType::class, $visit, [
            'disabled' => $readOnly,
            'teachers' => $teacherProvider->forContact($visit, $readOnly)
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_contact'));
                return $this->redirectToRoute('work_linked_training_contact_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wlt_contact'));
            }
        }

        $title = $translator->trans(
            $visit->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wlt_contact'
        );

        $breadcrumb = [
                ['fixed' => $title]
        ];

        return $this->render('wlt/contact/form.html.twig', [
            'menu_path' => 'work_linked_training_contact_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'contact' => $visit,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{academicYear}/{page}', name: 'work_linked_training_contact_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request                 $request,
        UserExtensionService    $userExtensionService,
        WltTeacherRepository    $wltTeacherRepository,
        WltGroupRepository      $wltGroupRepository,
        ProjectRepository       $projectRepository,
        Security                $security,
        TranslatorInterface     $translator,
        AcademicYearRepository  $academicYearRepository,
        ContactMethodRepository $contactMethodRepository,
        ManagerRegistry         $managerRegistry,
        AcademicYear            $academicYear = null,
        int                     $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $title = $translator->trans('title.list', [], 'wlt_contact');

        return $this->render('wlt/contact/list.html.twig', [
            'title' => $title,
            'domain' => 'wlt_contact',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar', name: 'work_linked_training_contact_operation', methods: ['POST'])]
    public function operation(
        Request $request,
        ContactRepository $visitRepository,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->all('items');
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('work_linked_training_contact_list');
        }

        $visits = $visitRepository->findAllInListById($items);
        foreach ($visits as $visit) {
            $this->denyAccessUnlessGranted(ContactVoter::MANAGE, $visit);
        }

        if ($request->request->get('confirm', '') === 'ok') {
            try {
                foreach ($visits as $visit) {
                    $em->remove($visit);
                }
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_contact'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wlt_contact'));
            }
            return $this->redirectToRoute('work_linked_training_contact_list');
        }

        $title = $translator->trans('title.delete', [], 'wlt_contact');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/contact/delete.html.twig', [
            'menu_path' => 'work_linked_training_contact_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $visits
        ]);
    }

    #[Route(path: '/informe/seguimiento/{academicYear}/{page}', name: 'work_linked_training_contact_educational_tutor_report_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function educationTutorReportList(
        Request                $request,
        UserExtensionService   $userExtensionService,
        ContactReportTeacherProvider $reportTeachers,
        AcademicYearRepository $academicYearRepository,
        ContactRepository      $contactRepository,
        TranslatorInterface    $translator,
        AcademicYear           $academicYear = null,
        int                    $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $title = $translator->trans(
            'title.educational_tutor_report',
            [],
            'wlt_contact'
        );

        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/contact/educational_tutor_report_list.html.twig', [
            'menu_path' => 'work_linked_training_contact_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'domain' => 'wlt_contact',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/informe/seguimiento/datos/{teacher}', name: 'work_linked_training_contact_educational_tutor_report_form', requirements: ['teacher' => '\d+'], methods: ['GET', 'POST'])]
    public function educationTutorReportForm(
        Request                 $request,
        UserExtensionService    $userExtensionService,
        ContactReportTeacherProvider $reportTeachers,
        ProjectRepository       $projectRepository,
        ContactRepository       $contactRepository,
        ContactMethodRepository $contactMethodRepository,
        TranslatorInterface     $translator,
        Environment             $engine,
        Teacher                 $teacher
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $academicYear = $teacher->getAcademicYear();
        assert($academicYear instanceof AcademicYear);
        $teachers = $reportTeachers->forAcademicYear($academicYear);

        if (!in_array($teacher, $teachers, true)) {
            throw $this->createAccessDeniedException();
        }

        $contactEducationalTutorReport = new ContactEducationalTutorReport();
        $contactEducationalTutorReport->setTeacher($teacher);
        $contactMethods = $contactMethodRepository->findEnabledByAcademicYear($academicYear);
        if (is_array($contactMethods)) {
            $contactMethods[] = null;
        }
        $contactEducationalTutorReport->setContactMethods($contactMethods);
        $projects = $projectRepository->findByEducationalTutor($teacher);
        array_unshift($projects, null);
        $contactEducationalTutorReport->setProjects($projects);

        $form = $this->createForm(ContactEducationalTutorReportType::class, $contactEducationalTutorReport, [
            'teacher' => $teacher
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                return $this->educationalTutorReport(
                    $translator,
                    $engine,
                    $contactRepository,
                    $contactEducationalTutorReport
                );
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.report_error', [], 'wlt_contact'));
            }
        }

        $title = $translator->trans(
            'title.educational_tutor_report.brief',
            [],
            'wlt_contact'
        ) . ' - ' . $teacher;

        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/contact/educational_tutor_report_form.html.twig', [
            'menu_path' => 'work_linked_training_contact_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'teacher' => $teacher,
            'form' => $form->createView()
        ]);
    }

    public function educationalTutorReport(
        TranslatorInterface $translator,
        Environment $engine,
        ContactRepository $contactRepository,
        ContactEducationalTutorReport $contactEducationalTutorReport
    ) {
        $teacher = $contactEducationalTutorReport->getTeacher();
        assert($teacher instanceof Teacher);
        $contactStats = $contactRepository->getContactMethodStatsByTeacherWorkcenterProjectsAndMethods(
            $teacher,
            $contactEducationalTutorReport->getWorkcenter(),
            $contactEducationalTutorReport->getProjects(),
            $contactEducationalTutorReport->getContactMethods()
        );

        $contacts = $contactRepository->findByTeacherWorkcenterProjectsAndMethods(
            $teacher,
            $contactEducationalTutorReport->getWorkcenter(),
            $contactEducationalTutorReport->getProjects(),
            $contactEducationalTutorReport->getContactMethods()
        );

        $html = $engine->render('wlt/contact/educational_tutor_report.html.twig', [
            'teacher' => $teacher,
            'contacts' => $contacts,
            'contact_stats' => $contactStats
        ]);

        $fileName = $translator->trans('title.educational_tutor_report.brief', [], 'wlt_contact')
            . ' - ' . $teacher . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }

    #[Route(path: '/informe/centros/{academicYear}/{page}', name: 'work_linked_training_contact_workcenter_report_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function workCenterReportList(
        Request                $request,
        UserExtensionService   $userExtensionService,
        ContactReportTeacherProvider $reportTeachers,
        AcademicYearRepository $academicYearRepository,
        ContactRepository      $contactRepository,
        TranslatorInterface    $translator,
        AcademicYear           $academicYear = null,
        int                    $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $organization);


        $title = $translator->trans(
            'title.workcenter_report',
            [],
            'wlt_contact'
        );

        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/contact/workcenter_report_list.html.twig', [
            'menu_path' => 'work_linked_training_contact_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'domain' => 'wlt_contact',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/informe/centros/datos/{workcenter}/{academicYear}', name: 'work_linked_training_contact_workcenter_report_form', requirements: ['workcenter' => '\d+', 'academicYear' => '\d+'], methods: ['GET', 'POST'])]
    public function workcenterReportForm(
        Request                 $request,
        UserExtensionService    $userExtensionService,
        ContactReportTeacherProvider $reportTeachers,
        ProjectRepository       $projectRepository,
        ContactRepository       $contactRepository,
        ContactMethodRepository $contactMethodRepository,
        TranslatorInterface     $translator,
        Environment             $engine,
        Workcenter              $workcenter,
        AcademicYear            $academicYear
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WltOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $teachers = $reportTeachers->forAcademicYear($academicYear);

        $workcenters = $contactRepository->findWorkcentersByTeachers($teachers);
        if (!in_array($workcenter, $workcenters, true)) {
            throw $this->createAccessDeniedException();
        }

        $contactWorkcenterReport = new ContactWorkcenterReport();
        $contactMethods = $contactMethodRepository->findEnabledByAcademicYear($academicYear);
        if (is_array($contactMethods)) {
            $contactMethods[] = null;
        }
        $contactWorkcenterReport->setContactMethods($contactMethods);
        $projects = $projectRepository->findByAcademicYearAndWorkcenter($academicYear, $workcenter);
        array_unshift($projects, null);
        $contactWorkcenterReport->setProjects($projects);

        $form = $this->createForm(ContactWorkcenterReportType::class, $contactWorkcenterReport, [
            'workcenter' => $workcenter,
            'academic_year' => $academicYear
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                return $this->workcenterReport(
                    $translator,
                    $engine,
                    $contactRepository,
                    $contactWorkcenterReport,
                    $workcenter,
                    $academicYear
                );
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.report_error', [], 'wlt_contact'));
            }
        }

        $title = $translator->trans(
            'title.workcenter_report.brief',
            [],
            'wlt_contact'
        ) . ' - ' . $workcenter;

        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wlt/contact/workcenter_report_form.html.twig', [
            'menu_path' => 'work_linked_training_contact_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }


    public function workcenterReport(
        TranslatorInterface $translator,
        Environment $engine,
        ContactRepository $contactRepository,
        ContactWorkcenterReport $contactWorkcenterReport,
        Workcenter $workcenter,
        AcademicYear $academicYear
    ) {
        $contactStats = $contactRepository->getContactMethodStatsByAcademicYearWorkcenterProjectsAndMethods(
            $academicYear,
            $workcenter,
            $contactWorkcenterReport->getProjects(),
            $contactWorkcenterReport->getContactMethods()
        );

        $contacts = $contactRepository->findByAcademicYearWorkcenterProjectsAndMethods(
            $academicYear,
            $workcenter,
            $contactWorkcenterReport->getProjects(),
            $contactWorkcenterReport->getContactMethods()
        );

        $html = $engine->render('wlt/contact/workcenter_report.html.twig', [
            'workcenter' => $workcenter,
            'academic_year' => $academicYear,
            'contacts' => $contacts,
            'contact_stats' => $contactStats
        ]);

        $fileName = $translator->trans('title.workcenter_report.brief', [], 'wlt_contact')
            . ' - ' . $workcenter->__toString() . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
}
