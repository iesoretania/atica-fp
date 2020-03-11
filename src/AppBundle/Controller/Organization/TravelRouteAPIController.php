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

namespace AppBundle\Controller\Organization;

use AppBundle\Entity\Edu\TravelRoute;
use AppBundle\Form\Type\Edu\NewTravelRouteType;
use AppBundle\Repository\Edu\TravelRouteRepository;
use AppBundle\Security\Edu\EduOrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

class TravelRouteAPIController extends Controller
{
    /**
     * @Route("/api/travel_route/new", name="api_travel_route_new", methods={"GET", "POST"})
     */
    public function apiNewTravelRouteAction(
        Request $request,
        UserExtensionService $userExtensionService
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_TEACHER, $organization);

        $em = $this->getDoctrine()->getManager();

        $newTravelRoute = new TravelRoute();
        $newTravelRoute
            ->setOrganization($organization);

        $form = $this->createForm(NewTravelRouteType::class, $newTravelRoute, [
            'action' => $this->generateUrl('api_travel_route_new'),
            'method' => 'POST'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($newTravelRoute);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'id' => $newTravelRoute->getId(),
                'name' => $newTravelRoute->getDescription()
            ]);
        }

        return $this->render('organization/travel_route/new_travel_route_form.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/api/travel_route/query", name="api_travel_route_query", methods={"GET"})
     */
    public function apiTravelRouteQuery(
        Request $request,
        UserExtensionService $userExtensionService,
        TravelRouteRepository $travelRouteRepository,
        TranslatorInterface $translator
    ) {
        $term = $request->get('q');

        $organization = $userExtensionService->getCurrentOrganization();

        $this->denyAccessUnlessGranted(EduOrganizationVoter::EDU_TEACHER, $organization);

        $routes = $travelRouteRepository->findByOrganizationAndQuery($organization, $term);

        $data = [];
        /** @var TravelRoute $travelRoute */
        foreach ($routes as $travelRoute) {
            $description = $travelRoute->getDescription() .
                ($travelRoute->isVerified()
                    ? (' (' . number_format(
                        $travelRoute->getDistance(),
                        2,
                        $translator->trans('format.decimal_separator', [], 'general'),
                        $translator->trans('format.thousand_separator', [], 'general')
                    ) . ' ' . $translator->trans('suffix.distance', [], 'general') . ')'
                    )
                    : (' ' . $translator->trans('form.unverified', [], 'wpt_travel_expense'))
                );
            $data[] = ['id' => $travelRoute->getId(), 'term' => $term, 'text' => $description];
        }

        $data[] =
            ['id' => 0, 'term' => $term, 'text' => $translator->trans('title.new', [], 'edu_travel_route')]
        ;

        return new JsonResponse($data);
    }
}
