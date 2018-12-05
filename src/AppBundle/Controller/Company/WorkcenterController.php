<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

namespace AppBundle\Controller\Company;

use AppBundle\Entity\Company;
use AppBundle\Entity\Workcenter;
use AppBundle\Form\Type\WorkcenterType;
use AppBundle\Repository\WorkcenterRepository;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @Route("/empresa")
 */
class WorkcenterController extends Controller
{
    /**
     * @Route("/{id}/sede/nueva", name="company_workcenter_new", methods={"GET", "POST"})
     **/
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Company $company
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $workcenter = new Workcenter();

        $workcenter
            ->setCompany($company)
            ->setAcademicYear($organization->getCurrentAcademicYear());

        $this->getDoctrine()->getManager()->persist($workcenter);

        return $this->formAction($request, $translator, $userExtensionService, $workcenter);
    }

    /**
     * @Route("/sede/{id}", name="company_workcenter_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function formAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Workcenter $workcenter
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $form = $this->createForm(WorkcenterType::class, $workcenter);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'workcenter'));
                return $this->redirectToRoute('company');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'workcenter'));
            }
        }

        $title = $translator->trans(
            $workcenter->getId() ? 'title.edit' : 'title.new',
            [],
            'workcenter'
        );

        $breadcrumb = [
            [
                'fixed' => $workcenter->getCompany()->getName(),
                'routeName' => 'company_workcenter_list',
                'routeParams' => ['id' => $workcenter->getCompany()->getId()]
            ],
            $workcenter->getId() ?
                ['fixed' => $workcenter->getName()] :
                ['fixed' => $this->get('translator')->trans('title.new', [], 'workcenter')]
        ];

        return $this->render('company/workcenter_form.html.twig', [
            'menu_path' => 'company',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/{id}/sede/listar/{page}", name="company_workcenter_list", requirements={"page" = "\d+"},
     *     defaults={"page" = 1},   methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        Company $company,
        $page = 1
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('w')
            ->from(Workcenter::class, 'w')
            ->orderBy('w.name')
            ->addOrderBy('w.city');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('w.name LIKE :tq')
                ->orWhere('w.city LIKE :tq')
                ->orWhere('w.emailAddress LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('w.company = :company')
            ->setParameter('company', $company);

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'workcenter');

        $breadcrumb = [
            ['fixed' => $company->getName()]
        ];

        return $this->render('company/workcenter_list.html.twig', [
            'menu_path' => 'company',
            'title' => $title,
            'breadcrumb'=> $breadcrumb,
            'pager' => $pager,
            'q' => $q,
            'company' => $company,
            'domain' => 'workcenter'
        ]);
    }

    /**
     * @Route("/{id}/sede/eliminar", name="company_workcenter_delete", methods={"POST"})
     */
    public function deleteAction(
        Request $request,
        WorkcenterRepository $workcenterRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        Company $company
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            return $this->redirectToRoute('company_workcenter_list', ['id' => $company->getId()]);
        }

        $workCenters = $workcenterRepository->findAllInListByIdAndCompany($items, $company);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $workcenterRepository->deleteFromList($workCenters);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'workcenter'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'workcenter'));
            }
            return $this->redirectToRoute('company_workcenter_list', ['id' => $company->getId()]);
        }

        $breadcrumb = [
            ['fixed' => $company->getName(), 'routeName' => 'company_workcenter_list', 'routeParams' => ['id' => $company->getId()]],
            ['fixed' => $this->get('translator')->trans('title.delete', [], 'workcenter')]
        ];

        return $this->render('company/workcenter_delete.html.twig', [
            'menu_path' => 'company',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'workcenter'),
            'items' => $workCenters
        ]);
    }
}
