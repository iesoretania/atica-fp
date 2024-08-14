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

namespace App\Controller\Organization;

use App\Entity\Edu\TravelRoute;
use App\Form\Type\Edu\TravelRouteType;
use App\Repository\Edu\TravelRouteRepository;
use App\Security\Edu\EduOrganizationVoter;
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

#[Route(path: '/centro/itinerario')]
class TravelRouteController extends AbstractController
{
    #[Route(path: '/nuevo', name: 'organization_travel_route_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry
    ): Response
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $travelRoute = new TravelRoute();
        $travelRoute
            ->setOrganization($organization);

        $managerRegistry->getManager()->persist($travelRoute);

        return $this->index(
            $request,
            $translator,
            $userExtensionService,
            $managerRegistry,
            $travelRoute
        );
    }

    #[Route(path: '/detalle/{id}', name: 'organization_travel_route_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        TravelRoute $travelRoute
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $em = $managerRegistry->getManager();

        $form = $this->createForm(TravelRouteType::class, $travelRoute);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_travel_route'));
                return $this->redirectToRoute('organization_travel_route_list');
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_travel_route'));
            }
        }

        $title = $translator->trans(
            $travelRoute->getId() !== null ? 'title.edit' : 'title.new',
            [],
            'edu_travel_route'
        );

        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('organization/travel_route/form.html.twig', [
            'menu_path' => 'organization_travel_route_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'form' => $form->createView()
        ]);
    }

    #[Route(path: '/listar/{page}', name: 'organization_travel_route_list', requirements: ['page' => '\d+'], methods: ['GET'])]
    public function list(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        int $page = 1
    ): Response {

        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();
        $queryBuilder
            ->select('tr')
            ->from(TravelRoute::class, 'tr')
            ->addOrderBy('tr.description', 'ASC');

        $q = $request->get('q');

        if ($q) {
            $queryBuilder
                ->orWhere('tr.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
                ->andWhere('tr.organization = :organization')
                ->setParameter('organization', $organization);

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'edu_travel_route');

        return $this->render('organization/travel_route/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'edu_travel_route'
        ]);
    }

    #[Route(path: '/eliminar', name: 'organization_travel_route_operation', methods: ['POST'])]
    public function operation(
        Request $request,
        TravelRouteRepository $travelRouteRepository,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator
    ): Response {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $em = $managerRegistry->getManager();

        $items = $request->request->get('items', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_travel_route_list');
        }

        $travelRoutes = $travelRouteRepository->findAllInListById($items);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $travelRouteRepository->deleteFromList($travelRoutes);
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_travel_route'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'edu_travel_route'));
            }
            return $this->redirectToRoute('organization_travel_route_list');
        }

        $title = $translator->trans('title.delete', [], 'edu_travel_route');
        $breadcrumb = [
            ['fixed' => $title]
        ];

        return $this->render('organization/travel_route/delete.html.twig', [
            'menu_path' => 'organization_travel_route_list',
            'breadcrumb' => $breadcrumb,
            'title' => $title,
            'items' => $travelRoutes
        ]);
    }
}
