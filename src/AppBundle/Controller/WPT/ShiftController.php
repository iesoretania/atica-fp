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
use AppBundle\Entity\WPT\Shift;
use AppBundle\Form\Type\WPT\ShiftStudentEnrollmentType;
use AppBundle\Form\Type\WPT\ShiftType;
use AppBundle\Repository\Edu\AcademicYearRepository;
use AppBundle\Repository\WPT\ShiftRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Security\WPT\ShiftVoter;
use AppBundle\Security\WPT\WPTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/fct/convocatoria")
 */
class ShiftController extends Controller
{
    /**
     * @Route("/listar/{academicYear}/{page}", name="workplace_training_shift_list",
     *     requirements={"academicYear" = "\d+", "page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        AcademicYearRepository $academicYearRepository,
        TranslatorInterface $translator,
        AcademicYear $academicYear = null,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if (!$academicYear) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('s')
            ->distinct(true)
            ->from(Shift::class, 's')
            ->join('s.subject', 'su')
            ->leftJoin('su.grade', 'gr')
            ->leftJoin('gr.groups', 'g')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('s.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('s.name LIKE :tq')
                ->orWhere('p.name LIKE :tq')
                ->orWhere('gr.name LIKE :tq')
                ->orWhere('tr.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        if (!$isManager) {
            $queryBuilder
                ->andWhere('p.manager = :manager OR (d.head IS NOT NULL AND h.person = :manager)')
                ->setParameter('manager', $this->getUser()->getPerson());
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

        $title = $translator->trans('title.list', [], 'wpt_shift');

        return $this->render('wpt/shift/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_shift',
            'academic_year' => $academicYear,
            'academic_years' => $academicYearRepository->findAllByOrganization($organization)
        ]);
    }

    /**
     * @Route("/nueva/{academicYear}", name="workplace_training_shift_new",
     *     requirements={"academicYear" = "\d+"}, methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYear $academicYear
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);

        $shift = new Shift();

        $this->getDoctrine()->getManager()->persist($shift);

        return $this->editAction($request, $userExtensionService, $translator, $shift, $academicYear);
    }

    /**
     * @Route("/{id}", name="workplace_training_shift_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        Shift $shift,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);

        $academicYear = $shift->getGrade() ? $shift->getGrade()->getTraining()->getAcademicYear() : $academicYear;
        $organization = $userExtensionService->getCurrentOrganization();
        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(ShiftType::class, $shift, [
            'lock_manager' => !$isManager,
            'academic_year' => $academicYear
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_shift'));
                return $this->redirectToRoute('workplace_training_shift_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_shift'));
            }
        }

        $title = $translator->trans(
            $shift->getId() ? 'title.edit' : 'title.new',
            [],
            'wpt_shift'
        );

        $breadcrumb = [
            $shift->getId() ?
                ['fixed' => $shift->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'wpt_shift')]
        ];

        return $this->render('wpt/shift/form.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/estudiantes/{id}", name="workplace_training_shift_student_enrollment",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function studentEnrollmentsAction(
        Request $request,
        TranslatorInterface $translator,
        Shift $shift
    ) {
        $organization = $shift->getGrade()->getTraining()->getAcademicYear()->getOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(ShiftStudentEnrollmentType::class, $shift, [
            'groups' => $shift->getGrade()->getGroups()
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'wpt_shift'));
                return $this->redirectToRoute('workplace_training_shift_list', [
                    'academicYear' => $shift->getGrade()->getTraining()->getAcademicYear()->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'wpt_shift'));
            }
        }

        $title = $translator->trans(
            'title.student_enrollment',
            [],
            'wpt_shift'
        );

        $breadcrumb = [
            ['fixed' => $shift->getName()],
            ['fixed' => $translator->trans('title.student_enrollment', [], 'wpt_shift')]
        ];

        return $this->render('wpt/shift/student_enrollment_form.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/eliminar/{academicYear}", name="workplace_training_shift_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        ShiftRepository $shiftRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        AcademicYear $academicYear
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('workplace_training_shift_list');
        }
        $selectedItems = $shiftRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $shiftRepository->deleteFromList($selectedItems);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'wpt_shift'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'wpt_shift'));
            }
            return $this->redirectToRoute('workplace_training_shift_list', ['academicYear' => $academicYear->getId()]);
        }

        $title = $translator->trans('title.delete', [], 'wpt_shift');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wpt/shift/delete.html.twig', [
            'menu_path' => 'workplace_training_shift_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $selectedItems
        ]);
    }
}
