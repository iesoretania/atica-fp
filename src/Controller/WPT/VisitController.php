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
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WPT\Contact;
use App\Form\Type\WPT\VisitType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WPT\ContactRepository;
use App\Repository\WPT\WPTGroupRepository;
use App\Repository\WPT\WPTTeacherRepository;
use App\Security\Edu\EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WPT\VisitVoter;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Mpdf\Mpdf;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

#[Route(path: '/fct/visita')]
class VisitController extends AbstractController
{
    #[Route(path: '/nueva/{id}', requirements: ['id' => '\d+'], name: 'workplace_training_visit_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        WPTTeacherRepository $WPTTeacherRepository,
        WPTGroupRepository $WPTGroupRepository,
        ManagerRegistry $managerRegistry,
        Teacher $teacher
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_CREATE_VISIT, $organization);

        $visit = new Contact();
        $visit
            ->setTeacher($teacher)
            ->setDateTime(new \DateTime());

        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $managerRegistry->getManager()->persist($visit);

        return $this->index(
            $request,
            $translator,
            $userExtensionService,
            $security,
            $WPTTeacherRepository,
            $WPTGroupRepository,
            $managerRegistry,
            $visit
        );
    }

    #[Route(path: '/{id}', name: 'workplace_training_visit_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        WPTTeacherRepository $WPTTeacherRepository,
        WPTGroupRepository $WPTGroupRepository,
        ManagerRegistry $managerRegistry,
        Contact $visit
    ): Response {
        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $organization = $userExtensionService->getCurrentOrganization();
        $academicYear = $visit->getTeacher() instanceof Teacher ? $visit->getTeacher()->getAcademicYear() : $organization->getCurrentAcademicYear();

        $em = $managerRegistry->getManager();

        $readOnly = !$this->isGranted(VisitVoter::MANAGE, $visit);

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isDepartmentHead = $security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $teachers = $this->getTeachersByAcademicYearAndUser(
            $WPTTeacherRepository,
            $WPTGroupRepository,
            $academicYear,
            $isManager,
            $isDepartmentHead,
            $readOnly
        );

        $form = $this->createForm(VisitType::class, $visit, [
            'disabled' => $readOnly,
            'teachers' => $teachers
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_visit'));
                return $this->redirectToRoute(
                    'workplace_training_visit_detail_list',
                    ['id' => $visit->getTeacher()->getId()]
                );
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_visit'));
            }
        }

        $title = $translator->trans(
            $visit->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wpt_visit'
        );

        $breadcrumb = [
            [
                'fixed' => (string) $visit->getTeacher(),
                'routeName' => 'workplace_training_visit_detail_list',
                'routeParams' => ['id' => $visit->getTeacher()->getId()]
            ],
            ['fixed' => $title]
        ];

        return $this->render('wpt/visit/form.html.twig', [
            'menu_path' => 'workplace_training_visit_teacher_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/listar/{page}', name: 'workplace_training_visit_detail_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Teacher $teacher,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_VISIT, $organization);

        // probar si tiene acceso a las visitas de este usuario
        $visit = new Contact();
        $visit
            ->setTeacher($teacher)
            ->setDateTime(new \DateTime());

        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $allowNew = $this->isGranted(WPTOrganizationVoter::WPT_CREATE_VISIT, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('v')
            ->distinct(true)
            ->addSelect('a')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->from(Contact::class, 'v')
            ->join('v.workcenter', 'w')
            ->join('w.company', 'c')
            ->leftJoin('v.agreements', 'a')
            ->leftJoin('v.studentEnrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->addOrderBy('v.dateTime', 'DESC');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('a.name LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('(sep.firstName IS NOT NULL AND (sep.firstName LIKE :tq OR sep.lastName LIKE :tq))')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('v.teacher = :teacher')
            ->setParameter('teacher', $teacher);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $teacher . ' - ' . $translator->trans('title.list', [], 'wpt_visit');

        $breadcrumb = [
            [
                'fixed' => (string) $teacher,
            ]
        ];

        return $this->render('wpt/visit/list.html.twig', [
            'menu_path' => 'workplace_training_visit_teacher_list',
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_visit',
            'allow_new' => $allowNew,
            'teacher' => $teacher
        ]);
    }

    #[Route(path: '/resumen/{academicYear}/{page}', name: 'workplace_training_visit_teacher_list', requirements: ['academicYear' => '\d+', 'page' => '\d+'], methods: ['GET'])]
    public function teacherList(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        WPTTeacherRepository $WPTTeacherRepository,
        WPTGroupRepository $WPTGroupRepository,
        AcademicYearRepository $academicYearRepository,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear instanceof AcademicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_VISIT, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $isDepartmentHead = $this->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $teachers = $this->getTeachersByAcademicYearAndUser(
            $WPTTeacherRepository,
            $WPTGroupRepository,
            $academicYear,
            $isManager,
            $isDepartmentHead,
            false
        );

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('t')
            ->addSelect('p')
            ->addSelect('COUNT(v)')
            ->addSelect('COUNT(DISTINCT a)')
            ->from(Teacher::class, 't')
            ->join('t.person', 'p')
            ->leftJoin(Contact::class, 'v', 'WITH', 'v.teacher = t')
            ->leftJoin('v.agreements', 'a')
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

        $queryBuilder
            ->andWhere('t IN (:teachers)')
            ->setParameter('teachers', $teachers);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.teacher_list', [], 'wpt_visit');

        return $this->render('wpt/visit/teacher_list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_travel_expense',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    #[Route(path: '/eliminar/{id}', name: 'workplace_training_visit_operation', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function operation(
        Request              $request,
        ContactRepository    $visitRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface  $translator,
        ManagerRegistry      $managerRegistry,
        Teacher              $teacher
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_VISIT, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('workplace_training_visit_detail_list', ['id' => $teacher->getId()]);
        }

        $visits = $visitRepository->findAllInListById($items);
        foreach ($visits as $visit) {
            $this->denyAccessUnlessGranted(VisitVoter::MANAGE, $visit);
        }

        if ($request->get('confirm', '') === 'ok') {
            try {
                foreach ($visits as $visit) {
                    $em->remove($visit);
                }
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wpt_visit'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wpt_visit'));
            }
            return $this->redirectToRoute('workplace_training_visit_detail_list', ['id' => $teacher->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'wpt_visit');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wpt/visit/delete.html.twig', [
            'menu_path' => 'workplace_training_visit_teacher_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $visits
        ]);
    }

    #[Route(path: '/{id}/descargar', requirements: ['id' => '\d+'], name: 'workplace_training_visit_report', methods: ['GET'])]
    public function visitSummaryReport(
        Environment          $engine,
        TranslatorInterface  $translator,
        ContactRepository    $visitRepository,
        Teacher              $teacher
    ) {
        $visit = new Contact();
        $visit
            ->setTeacher($teacher);

        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

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

            $title = $translator->trans('title.report', [], 'wpt_visit_report')
                . ' - ' . $teacher->getPerson();

            $fileName = $title . '.pdf';

            $visits = $visitRepository->findByTeacherOrderByDateTime($teacher);

            $html = $engine->render('wpt/visit/teacher_report.html.twig', [
                'teacher' => $teacher,
                'visits' => $visits,
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

    /**
     * @param $isManager
     * @param $isDepartmentHead
     * @param $readOnly
     * @return array|int|string
     */
    private function getTeachersByAcademicYearAndUser(
        WPTTeacherRepository $WPTTeacherRepository,
        WPTGroupRepository $WPTGroupRepository,
        AcademicYear $academicYear,
        bool $isManager,
        bool $isDepartmentHead,
        bool $readOnly
    ) {
        $groups = [];
        $teacher = null;

        if (false === $isManager) {
            /** @var Person $person */
            $person = $this->getUser();

            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor -> ver sólo sus grupos
            $teacher =
                $WPTTeacherRepository->findOneByAcademicYearAndPerson($academicYear, $person);

            if ($teacher) {
                $groups = $WPTGroupRepository
                    ->findByAcademicYearAndWPTGroupTutorOrDepartmentHeadPerson($academicYear, $person);
            }
        }

        if (!$isManager && !$isDepartmentHead && $teacher && !$readOnly) {
            $teachers = [$teacher];
        } elseif ($groups) {
            $teachers = $WPTTeacherRepository->findByGroups($groups);
        } else {
            $teachers = $WPTTeacherRepository->findByAcademicYear($academicYear);
        }
        return $teachers;
    }

}
