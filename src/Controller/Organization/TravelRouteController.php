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

namespace App\Controller\Organization;

use App\Entity\Edu\TravelRoute;
use App\Form\Type\Edu\TravelRouteType;
use App\Repository\Edu\TravelRouteRepository;
use App\Security\Edu\EduOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/centro/itinerario")
 */
class TravelRouteController extends AbstractController
{
    /**
     * @Route("/nuevo", name="organization_travel_route_new", methods={"GET", "POST"})
     */
    public function newAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $travelRoute = new TravelRoute();
        $travelRoute
            ->setOrganization($organization);

        $this->getDoctrine()->getManager()->persist($travelRoute);

        return $this->indexAction(
            $request,
            $translator,
            $userExtensionService,
            $travelRoute
        );
    }

    /**
     * @Route("/detalle/{id}", name="organization_travel_route_edit",
     *     requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        TravelRoute $travelRoute
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(TravelRouteType::class, $travelRoute);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'edu_travel_route'));
                return $this->redirectToRoute('organization_travel_route_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.error', [], 'edu_travel_route'));
            }
        }

        $title = $translator->trans(
            $travelRoute->getId() !== 0 ? 'title.edit' : 'title.new',
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

    /**
     * @Route("/listar/{page}", name="organization_travel_route_list",
     *     requirements={"page" = "\d+"}, methods={"GET"})
     */
    public function listAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator,
        $page = 1
    ) {

        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();
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
        } catch (OutOfRangeCurrentPageException $e) {
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

    /**
     * @Route("/eliminar", name="organization_travel_route_operation", methods={"POST"})
     */
    public function operationAction(
        Request $request,
        TravelRouteRepository $travelRouteRepository,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $em = $this->getDoctrine()->getManager();

        $items = $request->request->get('items', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            return $this->redirectToRoute('organization_travel_route_list');
        }

        $travelRoutes = $travelRouteRepository->findAllInListById($items);

        if ($request->get('confirm', '') === 'ok') {
            try {
                $travelRouteRepository->deleteFromList($travelRoutes);
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'edu_travel_route'));
            } catch (\Exception $e) {
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
