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

namespace AppBundle\Controller\Organization;

use AppBundle\Entity\Location;
use AppBundle\Entity\Organization;
use AppBundle\Form\Type\LocationType;
use AppBundle\Security\LocationVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/centro/dependencias")
 */
class LocationController extends Controller
{
    /**
     * @Route("/nueva", name="organization_location_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="organization_location_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(UserExtensionService $userExtensionService, Request $request, Location $location = null)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);
        if ($location) {
            $this->denyAccessUnlessGranted(LocationVoter::MANAGE, $location);
        }

        $em = $this->getDoctrine()->getManager();

        if (null === $location) {
            $location = new Location();
            $location->setOrganization($organization);
            $em->persist($location);
        }

        $form = $this->createForm(LocationType::class, $location, [
            'new' => $location->getId() === null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'location'));
                return $this->redirectToRoute('organization_location_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.save_error', [], 'location'));
            }
        }

        return $this->render('location/form.html.twig', [
            'menu_path' => 'organization_location_list',
            'breadcrumb' => [['fixed' => $location->getId() ? (string) $location : $this->get('translator')->trans('title.new', [], 'location')]],
            'title' => $this->get('translator')->trans($organization->getId() ? 'title.edit' : 'title.new', [], 'location'),
            'form' => $form->createView(),
            'user' => $location
        ]);
    }

    /**
     * @Route("/listar/{page}", name="organization_location_list", requirements={"page" = "\d+"}, defaults={"page" = "1"}, methods={"GET"})
     */
    public function listAction($page, Request $request)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('l')
            ->from('AppBundle:Location', 'l')
            ->orderBy('l.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('l.name LIKE :tq')
                ->orWhere('l.additionalData LIKE :tq')
                ->orWhere('l.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'location');

        return $this->render('location/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'location'
        ]);
    }

    /**
     * @Route("/operacion", name="organization_location_operation", methods={"POST"})
     */
    public function operationAction(Request $request, UserExtensionService $userExtensionService)
    {
        $organization = $userExtensionService->getCurrentOrganization();

        list($redirect, $locations) = $this->processOperations($request, $organization);

        if ($redirect) {
            return $this->redirectToRoute('organization_location_list');
        }

        return $this->render('location/delete.html.twig', [
            'menu_path' => 'organization_location_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'location')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'location'),
            'locations' => $locations
        ]);
    }

    /**
     * Borrar los datos de las dependencias pasadas como parámetros
     *
     * @param Location[] $locations
     */
    private function deleteLocations($locations)
    {
        $em = $this->getDoctrine()->getManager();

        /* Desasociar elementos a las dependencias */
        $em->createQueryBuilder()
            ->update('AppBundle:ICT\\Element', 'e')
            ->set('e.location = NULL')
            ->where('e.location IN (:items)')
            ->setParameter('items', $locations)
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->update('AppBundle:Location', 'l')
            ->set('l.parent = NULL')
            ->where('l.parent IN (:items)')
            ->setParameter('items', $locations)
            ->getQuery()
            ->execute();

        /* Finalmente eliminamos las dependencias */
        $em->createQueryBuilder()
            ->delete('AppBundle:Location', 'l')
            ->where('l IN (:items)')
            ->setParameter('items', $locations)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Request $request
     * @param $locations
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @return bool
     */
    private function processRemoveLocations(Request $request, $locations, $em)
    {
        $redirect = false;
        if ($request->get('confirm', '') === 'ok') {
            try {
                $this->deleteLocations($locations);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'location'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'location'));
            }
            $redirect = true;
        }
        return $redirect;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function processOperations(Request $request, Organization $organization)
    {
        $em = $this->getDoctrine()->getManager();

        $redirect = false;

        $items = $request->request->get('locations', []);
        if (count($items) === 0) {
            $redirect = true;
        }

        $locations = [];
        if (!$redirect) {
            $locations = $em->getRepository('AppBundle:Location')->findInListByIdAndOrganization($items, $organization);
            $redirect = $this->processRemoveLocations($request, $locations, $em);
        }
        return array($redirect, $locations);
    }
}
