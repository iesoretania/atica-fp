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
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WPT\Visit;
use App\Form\Type\WPT\VisitType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WPT\VisitRepository;
use App\Repository\WPT\WPTGroupRepository;
use App\Repository\WPT\WPTTeacherRepository;
use App\Security\Edu\EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Security\WPT\VisitVoter;
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
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use TFox\MpdfPortBundle\Service\MpdfService;
use Twig\Environment;

/**
 * @Route("/fct/visita")
 */
class VisitController extends AbstractController
{
    /**
     * @Route("/nueva/{id}",  requirements={"id" = "\d+"},
     *     name="workplace_training_visit_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        WPTTeacherRepository $WPTTeacherRepository,
        WPTGroupRepository $WPTGroupRepository,
        Teacher $teacher
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_CREATE_VISIT, $organization);

        $visit = new Visit();
        $visit
            ->setTeacher($teacher)
            ->setDateTime(new \DateTime());

        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $this->getDoctrine()->getManager()->persist($visit);

        return $this->indexAction(
            $request,
            $translator,
            $userExtensionService,
            $security,
            $WPTTeacherRepository,
            $WPTGroupRepository,
            $visit
        );
    }

    /**
     * @Route("/{id}", name="workplace_training_visit_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        WPTTeacherRepository $WPTTeacherRepository,
        WPTGroupRepository $WPTGroupRepository,
        Visit $visit
    ) {
        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $organization = $userExtensionService->getCurrentOrganization();
        if ($visit->getTeacher()) {
            $academicYear = $visit->getTeacher()->getAcademicYear();
        } else {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $em = $this->getDoctrine()->getManager();

        $readOnly = !$this->isGranted(VisitVoter::MANAGE, $visit);

        $isManager = $security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isDepartmentHead = $security->isGranted(EduOrganizationVoter::EDU_DEPARTMENT_HEAD, $organization);

        $teacher = null;

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
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_visit'));
            }
        }

        $title = $translator->trans(
            $visit->getId() ? 'title.edit' : 'title.new',
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

    /**
     * @Route("/{id}/listar/{page}", name="workplace_training_visit_detail_list",
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
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_VISIT, $organization);

        // probar si tiene acceso a las visitas de este usuario
        $visit = new Visit();
        $visit
            ->setTeacher($teacher)
            ->setDateTime(new \DateTime());

        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $allowNew = $this->isGranted(WPTOrganizationVoter::WPT_CREATE_VISIT, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('v')
            ->distinct(true)
            ->addSelect('a')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->from(Visit::class, 'v')
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
        } catch (OutOfRangeCurrentPageException $e) {
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

    /**
     * @Route("/resumen/{academicYear}/{page}", name="workplace_training_visit_teacher_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function teacherListAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        WPTTeacherRepository $WPTTeacherRepository,
        WPTGroupRepository $WPTGroupRepository,
        AcademicYearRepository $academicYearRepository,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (null === $academicYear) {
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
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('t')
            ->addSelect('p')
            ->addSelect('COUNT(v)')
            ->addSelect('COUNT(DISTINCT a)')
            ->from(Teacher::class, 't')
            ->join('t.person', 'p')
            ->leftJoin(Visit::class, 'v', 'WITH', 'v.teacher = t')
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
        } catch (OutOfRangeCurrentPageException $e) {
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

    /**
     * @Route("/eliminar/{id}", name="workplace_training_visit_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        VisitRepository $visitRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Teacher $teacher
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_VISIT, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
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
            } catch (\Exception $e) {
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

    /**
     * @Route("/{id}/descargar",
     *     requirements={"id" = "\d+"}, name="workplace_training_visit_report", methods={"GET"})
     */
    public function visitSummaryReportAction(
        Environment $engine,
        TranslatorInterface $translator,
        WPTTeacherRepository $wptTeacherRepository,
        VisitRepository $visitRepository,
        Teacher $teacher
    ) {
        $visit = new Visit();
        $visit
            ->setTeacher($teacher);

        $this->denyAccessUnlessGranted(VisitVoter::ACCESS, $visit);

        $mpdfService = new MpdfService();
        $mpdfService->setAddDefaultConstructorArgs(false);

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
     * @param WPTTeacherRepository $WPTTeacherRepository
     * @param WPTGroupRepository $WPTGroupRepository
     * @param AcademicYear $academicYear
     * @param $isManager
     * @param $isDepartmentHead
     * @param $readOnly
     * @return array|int|string
     */
    private function getTeachersByAcademicYearAndUser(
        WPTTeacherRepository $WPTTeacherRepository,
        WPTGroupRepository $WPTGroupRepository,
        AcademicYear $academicYear,
        $isManager,
        $isDepartmentHead,
        $readOnly
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
