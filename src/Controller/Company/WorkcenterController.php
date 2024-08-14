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

namespace App\Controller\Company;

use App\Entity\Company;
use App\Entity\Workcenter;
use App\Form\Type\WorkcenterType;
use App\Repository\WorkcenterRepository;
use App\Security\OrganizationVoter;
use App\Security\WorkcenterVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route(path: '/empresa')]
class WorkcenterController extends AbstractController
{
    #[Route(path: '/{id}/sede/nueva', name: 'company_workcenter_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        Company $company
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $workcenter = new Workcenter();

        $workcenter
            ->setCompany($company);

        $managerRegistry->getManager()->persist($workcenter);

        return $this->form($request, $translator, $managerRegistry, $workcenter);
    }

    #[Route(path: '/sede/{id}', name: 'company_workcenter_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function form(
        Request $request,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Workcenter $workcenter
    ): Response {
        $this->denyAccessUnlessGranted(WorkcenterVoter::MANAGE, $workcenter);

        $form = $this->createForm(WorkcenterType::class, $workcenter);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $managerRegistry->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'workcenter'));
                return $this->redirectToRoute('company_workcenter_list', ['id' => $workcenter->getCompany()->getId()]);
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'workcenter'));
            }
        }

        $title = $translator->trans(
            $workcenter->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'workcenter'
        );

        $breadcrumb = [
            [
                'fixed' => $workcenter->getCompany()->getName(),
                'routeName' => 'company_edit',
                'routeParams' => ['id' => $workcenter->getCompany()->getId()]
            ],
            [
                'fixed' => $translator->trans('title.list', [], 'workcenter'),
                'routeName' => 'company_workcenter_list',
                'routeParams' => ['id' => $workcenter->getCompany()->getId()]
            ],
            $workcenter->getId() !== null ?
                ['fixed' => $workcenter->getName()] :
                ['fixed' => $translator->trans('title.new', [], 'workcenter')]
        ];

        return $this->render('company/workcenter_form.html.twig', [
            'menu_path' => 'company',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/{id}/sede/listar/{page}', name: 'company_workcenter_list', requirements: ['page' => '\d+'], defaults: ['page' => 1], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Company $company,
        int $page = 1
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('w')
            ->from(Workcenter::class, 'w')
            ->orderBy('w.name')
            ->addOrderBy('w.city');

        $q = $request->get('q');
        if ($q) {
            $queryBuilder
                ->where('w.name LIKE :tq')
                ->orWhere('w.city LIKE :tq')
                ->orWhere('w.emailAddress LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }

        $queryBuilder
            ->andWhere('w.company = :company')
            ->setParameter('company', $company);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $company->getName() . ' - ' . $translator->trans('title.list', [], 'workcenter');

        $breadcrumb = [
            [
                'fixed' => $company->getName(),
                'routeName' => 'company',
                'routeParams' => ['id' => $company->getId()]
            ],
            [
                'fixed' => $translator->trans('title.list', [], 'workcenter')
            ]
        ];

        return $this->render('company/workcenter_list.html.twig', [
            'menu_path' => 'company',
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'pager' => $pager,
            'q' => $q,
            'company' => $company,
            'domain' => 'workcenter'
        ]);
    }

    #[Route(path: '/{id}/sede/eliminar', name: 'company_workcenter_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        WorkcenterRepository $workcenterRepository,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        Company $company
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE_COMPANIES, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('company_workcenter_list', ['id' => $company->getId()]);
        }

        $workCenters = $workcenterRepository->findAllInListByIdAndCompany($items, $company);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $workcenterRepository->deleteFromList($workCenters);

                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'workcenter'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'workcenter'));
            }
            return $this->redirectToRoute('company_workcenter_list', ['id' => $company->getId()]);
        }

        $breadcrumb = [
            [
                'fixed' => $company->getName(),
                'routeName' => 'company_edit',
                'routeParams' => ['id' => $company->getId()]
            ],
            [
                'fixed' => $translator->trans('title.list', [], 'workcenter'),
                'routeName' => 'company_workcenter_list',
                'routeParams' => ['id' => $company->getId()]
            ],
            ['fixed' => $translator->trans('title.delete', [], 'workcenter')]
        ];

        return $this->render('company/workcenter_delete.html.twig', [
            'menu_path' => 'company',
            'breadcrumb' => $breadcrumb,
            'title' => $translator->trans('title.delete', [], 'workcenter'),
            'items' => $workCenters
        ]);
    }
}
