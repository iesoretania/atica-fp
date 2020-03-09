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

use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WPT\TravelExpense;
use AppBundle\Form\Type\WPT\TravelExpenseType;
use AppBundle\Repository\WPT\AgreementRepository;
use AppBundle\Repository\WPT\VisitRepository;
use AppBundle\Security\WPT\TravelExpenseVoter;
use AppBundle\Security\WPT\VisitVoter;
use AppBundle\Security\WPT\WPTOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/fct/desplazamiento")
 */
class TravelExpenseController extends Controller
{
    /**
     * @Route("/nuevo/{id}", name="workplace_training_travel_expense_new",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Security $security,
        AgreementRepository $agreementRepository,
        Teacher $teacher
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_CREATE_VISIT, $organization);

        $travelExpense = new TravelExpense();
        $travelExpense
            ->setTeacher($teacher)
            ->setFromDateTime(new \DateTime())
            ->setToDateTime(new \DateTime());

        $this->getDoctrine()->getManager()->persist($travelExpense);

        return $this->indexAction(
            $request,
            $translator,
            $userExtensionService,
            $security,
            $agreementRepository,
            $travelExpense
        );
    }

    /**
     * @Route("/{id}", name="workplace_training_travel_expense_edit",
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

        if (count($agreements) === 0) {
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
            $travelExpense->getId() ? 'title.edit' : 'title.new',
            [],
            'wpt_travel_expense'
        );

        $breadcrumb = [
                ['fixed' => $title]
        ];

        return $this->render('wpt/travel_expense/form.html.twig', [
            'menu_path' => 'workplace_training_visit_list',
            'academic_year' => $academicYear,
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'read_only' => $readOnly,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{id}/{page}", name="workplace_training_travel_expense_detail_list",
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
        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_EDUCATIONAL_TUTOR, $organization);

        $allowNew = $this->isGranted(WPTOrganizationVoter::WPT_CREATE_VISIT, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('te')
            ->distinct(true)
            ->addSelect('a')
            ->addSelect('tr')
            ->from(TravelExpense::class, 'te')
            ->join('te.travelRoute', 'tr')
            ->leftJoin('te.agreements', 'a')
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

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'wpt_travel_expense');

        return $this->render('wpt/travel_expense/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'wpt_travel_expense',
            'allow_new' => $allowNew,
            'teacher' => $teacher
        ]);
    }

    /**
     * @Route("/eliminar", name="workplace_training_travel_expense_operation",
     *     requirements={"id" = "\d+"}, methods={"POST"})
     */
    public function operationAction(
        Request $request,
        VisitRepository $visitRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(WPTOrganizationVoter::WPT_ACCESS_VISIT, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('workplace_training_visit_list');
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
            return $this->redirectToRoute('workplace_training_visit_list');
        }

        $title = $this->get('translator')->trans('title.delete', [], 'wpt_visit');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('wpt/visit/delete.html.twig', [
            'menu_path' => 'workplace_training_visit_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $visits
        ]);
    }
}
