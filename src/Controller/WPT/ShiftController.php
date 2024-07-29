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
use App\Entity\WPT\Shift;
use App\Form\Type\WPT\ShiftType;
use App\Repository\Edu\AcademicYearRepository;
use App\Repository\WPT\ActivityRepository;
use App\Repository\WPT\AgreementRepository;
use App\Repository\WPT\ShiftRepository;
use App\Security\OrganizationVoter;
use App\Security\WPT\ShiftVoter;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/fct/convocatoria")
 */
class ShiftController extends AbstractController
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
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear = null,
        int $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        if ($academicYear === null) {
            $academicYear = $organization->getCurrentAcademicYear();
        }

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);

        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

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

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('s.name LIKE :tq')
                ->orWhere('gr.name LIKE :tq')
                ->orWhere('tr.name LIKE :tq')
                ->orWhere('g.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        if (!$isManager) {
            $queryBuilder
                ->andWhere('d.head IS NOT NULL AND h.person = :manager')
                ->setParameter('manager', $this->getUser());
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
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);

        $shift = new Shift();
        $shift->setLocked(false);

        $managerRegistry->getManager()->persist($shift);

        return $this->editAction($request, $userExtensionService, $translator, $managerRegistry, $shift, $academicYear);
    }

    /**
     * @Route("/{id}", name="workplace_training_shift_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function editAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Shift $shift,
        AcademicYear $academicYear = null
    ) {
        $this->denyAccessUnlessGranted(ShiftVoter::MANAGE, $shift);

        $academicYear = $shift->getGrade() ? $shift->getGrade()->getTraining()->getAcademicYear() : $academicYear;
        $organization = $userExtensionService->getCurrentOrganization();
        $isManager = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $em = $managerRegistry->getManager();

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
            $shift->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'wpt_shift'
        );

        $breadcrumb = [
            $shift->getId() !== null ?
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
     * @Route("/eliminar/{academicYear}", name="workplace_training_shift_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        ShiftRepository $shiftRepository,
        AgreementRepository $agreementRepository,
        ActivityRepository $activityRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        AcademicYear $academicYear
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('workplace_training_shift_list');
        }
        $selectedItems = $shiftRepository->findAllInListByIdAndAcademicYear($items, $academicYear);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $agreementRepository->deleteFromShifts($selectedItems);
                $activityRepository->deleteFromShifts($selectedItems);
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
