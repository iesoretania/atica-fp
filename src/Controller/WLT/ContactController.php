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

namespace App\Controller\WLT;

use _PHPStan_8862d57cc\Symfony\Component\Finder\Exception\AccessDeniedException;
use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WLT\Contact;
use App\Entity\Workcenter;
use App\Form\Model\WLT\ContactEducationalTutorReport;
use App\Form\Model\WLT\ContactWorkcenterReport;
use App\Form\Type\WLT\ContactEducationalTutorReportType;
use App\Form\Type\WLT\ContactType;
use App\Form\Type\WLT\ContactWorkcenterReportType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\Edu\ContactMethodRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\WLT\ContactRepository;
use App\Repository\WLT\ProjectRepository;
use App\Repository\WLT\WLTGroupRepository;
use App\Repository\WLT\WLTTeacherRepository;
use App\Security\Edu\EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WLT\ContactVoter;
use App\Security\WLT\WLTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

/**
 * @Route("/dual/contacto")
 */
class ContactController extends AbstractController
{
    /**
     * @Route("/nuevo", name="work_linked_training_contact_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_CREATE_VISIT, $organization);

        $academicYear = $organization->getCurrentAcademicYear();
        /** @var Person $person */
        $person = $this->getUser();
        $teacher = $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

        $visit = new Contact();
        $visit
            ->setDateTime(new \DateTime());

        if ($teacher) {
            $visit->setTeacher($teacher);
        }

        $this->getDoctrine()->getManager()->persist($visit);

        return $this->indexAction(
            $request,
            $translator,
            $userExtensionService,
            $security,
            $teacherRepository,
            $wltGroupRepository,
            $wltTeacherRepository,
            $visit
        );
    }

    /**
     * @Route("/{id}", name="work_linked_training_contact_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository,
        Contact $visit
    ) {
        $this->denyAccessUnlessGranted(ContactVoter::ACCESS, $visit);

        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $visit->getTeacher()
            ? $visit->getTeacher()->getAcademicYear()
            : $organization->getCurrentAcademicYear();

        $em = $this->getDoctrine()->getManager();

        $readOnly = !$this->isGranted(ContactVoter::MANAGE, $visit);

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $teacher = null;

        if (!$isManager) {
            /** @var Person $person */
            $person = $this->getUser();

            if (!$isWltManager) {
                // no es administrador ni coordinador de FP:
                // puede ser jefe de departamento, tutor de grupo o profesor -> ver sólo sus grupos
                $teacher =
                    $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

                if ($teacher) {
                    $groups = $wltGroupRepository->findByAcademicYearAndWLTTeacherPerson($academicYear, $person);
                }
            } else {
                $groups = $wltGroupRepository->findByAcademicYearAndWLTTeacherPerson($academicYear, $person);
            }
        } else {
            $groups = $wltGroupRepository->findByOrganizationAndAcademicYear($organization, $academicYear);
        }
        $teachers = [];
        if (!$isManager && !$isDepartmentHead && $teacher && !$readOnly) {
            $teachers = [$teacher];
        } elseif ($groups) {
            $teachers = $wltTeacherRepository->findByGroupsOrEducationalTutor($groups, $academicYear);
        }

        $form = $this->createForm(ContactType::class, $visit, [
            'disabled' => $readOnly,
            'teachers' => $teachers
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wlt_contact'));
                return $this->redirectToRoute('work_linked_training_contact_list');
            } catch (\Exception $e) {
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
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{academicYear}/{page}", name="work_linked_training_contact_list",
     *     requirements={"page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $groupRepository,
        ProjectRepository $projectRepository,
        Security $security,
        TranslatorInterface $translator,
        AcademicYearRepository $academicYearRepository,
        ContactMethodRepository $contactMethodRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization);
        $allowNew = $this->isGranted(WLTOrganizationVoter::WLT_CREATE_VISIT, $organization);

        $mf = $request->get('mf');

        $methodCollection = [];
        if (null !== $mf) {
            $methodIdsCollection = explode(',', $mf);
            if (is_array($methodIdsCollection)) {
                $methodCollection = $contactMethodRepository
                    ->findAllInListByIdAndAcademicYear($methodIdsCollection, $academicYear);
            }
            if (in_array('0', $methodIdsCollection, true)) {
                $methodCollection[] = null;
            }
        }

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('v')
            ->distinct(true)
            ->addSelect('t')
            ->addSelect('p')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('pr')
            ->addSelect('se')
            ->addSelect('sep')
            ->addSelect('seg')
            ->from(Contact::class, 'v')
            ->join('v.teacher', 't')
            ->join('t.person', 'p')
            ->join('v.workcenter', 'w')
            ->join('w.company', 'c')
            ->leftJoin('v.projects', 'pr')
            ->leftJoin('v.studentEnrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->leftJoin('se.group', 'seg')
            ->addOrderBy('v.dateTime', 'DESC');

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(WLTOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $projects = [];

        /** @var Person $person */
        $person = $this->getUser();
        if (!$isWltManager && !$isManager && !$isDepartmentHead) {
            // no es administrador ni coordinador de FP ni jefe de familia profesional:
            // puede ser tutor de grupo  -> ver sólo visitas de los
            // estudiantes de sus grupos
            $groups = $groupRepository->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $projectRepository->findByManager($person);
        } elseif ($isDepartmentHead) {
            $projects = $projectRepository->findByDepartmentHeadPerson($person);
        }

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('(pr.name IS NOT NULL AND pr.name LIKE :tq)')
                ->orWhere('(seg.name IS NOT NULL AND seg.name LIKE :tq)')
                ->orWhere('(sep.firstName IS NOT NULL AND (sep.firstName LIKE :tq OR sep.lastName LIKE :tq))')
                ->setParameter('tq', '%'.$q.'%');
        }

        // ver siempre las propias
        /** @var Person $user */
        $user = $this->getUser();
        $teacher =
            $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $user);

        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR v.teacher = :teacher')
                ->setParameter('groups', $groups)
                ->setParameter('teacher', $teacher);
        }

        if ($projects) {
            $queryBuilder
                ->andWhere('pr IN (:projects) OR v.teacher = :teacher')
                ->setParameter('projects', $projects)
                ->setParameter('teacher', $teacher);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        if (count($methodCollection) > 0) {
            if (in_array(null, $methodCollection, true)) {
                $queryBuilder->andWhere('v.method IN (:methods) OR v.method IS NULL');
            } else {
                $queryBuilder->andWhere('v.method IN (:methods)');
            }

            $queryBuilder
                ->setParameter('methods', $methodCollection);
        }

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wlt_contact');

        $methods = $contactMethodRepository->findEnabledByAcademicYear($academicYear);

        $activeMethods = [];
        $activeMethods = array_merge($activeMethods, $methodCollection);

        return $this->render('wlt/contact/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_contact',
            'allow_new' => $allowNew,
            'methods' => $methods,
            'active_methods' => $activeMethods,
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/eliminar", name="work_linked_training_contact_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        ContactRepository $visitRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute('work_linked_training_contact_list');
        }

        $visits = $visitRepository->findAllInListById($items);
        foreach ($visits as $visit) {
            $this->denyAccessUnlessGranted(ContactVoter::MANAGE, $visit);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                foreach ($visits as $visit) {
                    $em->remove($visit);
                }
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wlt_contact'));
            } catch (\Exception $e) {
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

    /**
     * @param Security $security
     * @param $organization
     * @param TeacherRepository $teacherRepository
     * @param AcademicYear $academicYear
     * @param WLTGroupRepository $wltGroupRepository
     * @param WLTTeacherRepository $wltTeacherRepository
     * @return Teacher[]|array|\Doctrine\Common\Collections\Collection
     */
    private function getAllowedTeachers(
        Security $security,
        $organization,
        TeacherRepository $teacherRepository,
        AcademicYear $academicYear,
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository
    ) {
        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isDepartmentHead = $security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $groups = [];
        $currentUserTeacher = null;

        if (!$isManager) {
            /** @var Person $person */
            $person = $this->getUser();

            if (!$isWltManager) {
                // no es administrador ni coordinador de FP:
                // puede ser jefe de departamento, tutor de grupo o profesor -> ver sólo sus grupos
                $currentUserTeacher =
                    $teacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

                if ($currentUserTeacher) {
                    $groups = $wltGroupRepository->findByAcademicYearAndWLTTeacherPerson($academicYear, $person);
                }
            } else {
                $groups = $wltGroupRepository->findByAcademicYearAndWLTTeacherPerson($academicYear, $person);
            }
        } else {
            $groups = $wltGroupRepository->findByOrganizationAndAcademicYear($organization, $academicYear);
        }
        $teachers = [];
        if (!$isManager && !$isDepartmentHead && $currentUserTeacher) {
            $teachers = [$currentUserTeacher];
        } elseif ($groups) {
            $teachers = $wltTeacherRepository->findByGroupsOrEducationalTutor($groups, $academicYear);
        }
        return $teachers;
    }

    /**
     * @Route("/informe/seguimiento/{academicYear}/{page}", name="work_linked_training_contact_educational_tutor_report_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function educationTutorReportListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository,
        AcademicYearRepository $academicYearRepository,
        ContactRepository $contactRepository,
        TranslatorInterface $translator,
        AcademicYear $academicYear = null,
        int $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $teachers = $this->getAllowedTeachers(
            $security,
            $organization,
            $teacherRepository,
            $academicYear,
            $wltGroupRepository,
            $wltTeacherRepository
        );

        $q = $request->get('q');

        $queryBuilder = $contactRepository
            ->getTeacherStatsByIdAndFilterQueryBuilder($teachers, $q);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

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
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_contact',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/informe/seguimiento/datos/{teacher}", name="work_linked_training_contact_educational_tutor_report_form",
     *     requirements={"teacher" = "\d+"}, methods={"GET", "POST"})
     */
    public function educationTutorReportFormAction(
        Request $request,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository,
        ProjectRepository $projectRepository,
        ContactRepository $contactRepository,
        ContactMethodRepository $contactMethodRepository,
        TranslatorInterface $translator,
        Environment $engine,
        Teacher $teacher
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $academicYear = $teacher->getAcademicYear();
        $teachers = $this->getAllowedTeachers(
            $security,
            $organization,
            $teacherRepository,
            $academicYear,
            $wltGroupRepository,
            $wltTeacherRepository
        );

        if (!in_array($teacher, $teachers, true)) {
            throw new AccessDeniedException();
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
                return $this->educationalTutorReportAction(
                    $translator,
                    $engine,
                    $contactRepository,
                    $contactEducationalTutorReport
                );
            } catch (\Exception $e) {
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
            'form' => $form->createView()
        ]);
    }

    public function educationalTutorReportAction(
        TranslatorInterface $translator,
        Environment $engine,
        ContactRepository $contactRepository,
        ContactEducationalTutorReport $contactEducationalTutorReport
    ) {
        $teacher = $contactEducationalTutorReport->getTeacher();

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

    /**
     * @Route("/informe/centros/{academicYear}/{page}", name="work_linked_training_contact_workcenter_report_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function workCenterReportListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository,
        AcademicYearRepository $academicYearRepository,
        ContactRepository $contactRepository,
        TranslatorInterface $translator,
        AcademicYear $academicYear = null,
        int $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $teachers = $this->getAllowedTeachers(
            $security,
            $organization,
            $teacherRepository,
            $academicYear,
            $wltGroupRepository,
            $wltTeacherRepository
        );

        $workcenters = $contactRepository->findWorkcentersByTeachers($teachers);

        $q = $request->get('q');

        $queryBuilder = $contactRepository
            ->getWorkcenterStatsByIdAcademicYearAndFilterQueryBuilder($workcenters, $academicYear, $q);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

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
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wlt_contact',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/informe/centros/datos/{workcenter}/{academicYear}", name="work_linked_training_contact_workcenter_report_form",
     *     requirements={"workcenter" = "\d+", "academicYear" = "\d+"}, methods={"GET", "POST"})
     */
    public function workcenterReportFormAction(
        Request $request,
        UserExtensionService $userExtensionService,
        Security $security,
        TeacherRepository $teacherRepository,
        WLTGroupRepository $wltGroupRepository,
        WLTTeacherRepository $wltTeacherRepository,
        ProjectRepository $projectRepository,
        ContactRepository $contactRepository,
        ContactMethodRepository $contactMethodRepository,
        TranslatorInterface $translator,
        Environment $engine,
        Workcenter $workcenter,
        AcademicYear $academicYear
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization);

        $teachers = $this->getAllowedTeachers(
            $security,
            $organization,
            $teacherRepository,
            $academicYear,
            $wltGroupRepository,
            $wltTeacherRepository
        );

        $workcenters = $contactRepository->findWorkcentersByTeachers($teachers);
        if (!in_array($workcenter, $workcenters, true)) {
            throw new AccessDeniedException();
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
                return $this->workcenterReportAction(
                    $translator,
                    $engine,
                    $contactRepository,
                    $contactWorkcenterReport,
                    $workcenter,
                    $academicYear
                );
            } catch (\Exception $e) {
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


    public function workcenterReportAction(
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
            . ' - ' . $workcenter . '.pdf';

        $mpdfService = new MpdfService();
        ini_set("pcre.backtrack_limit", "5000000");

        $response = $mpdfService->generatePdfResponse($html);
        $response->headers->set('Content-disposition', 'inline; filename="' . $fileName . '"');

        return $response;
    }
}
