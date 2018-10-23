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

namespace AppBundle\Controller\ICT;

use AppBundle\Entity\ICT\Location;
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
 * @Route("/tic/dependencia")
 */
class LocationController extends Controller
{
    /**
     * @Route("/nueva", name="ict_location_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="ict_location_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        Request $request,
        Location $location = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();

        if ($location) {
            $this->denyAccessUnlessGranted(LocationVoter::ACCESS, $location);
            $isAdmin = $this->isGranted(LocationVoter::MANAGE, $location);
        } else {
            $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);
            $isAdmin = true;
        }

        $em = $this->getDoctrine()->getManager();

        if (null === $location) {
            $location = new Location();
            $location->setOrganization($organization);
            $em->persist($location);
        }

        $form = $this->createForm(LocationType::class, $location, [
            'new' => $location->getId() === null,
            'disabled' => !$isAdmin,
            'admin' => $isAdmin
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'ict_location'));
                return $this->redirectToRoute('ict_location_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.save_error', [], 'ict_location'));
            }
        }

        return $this->render('ict/location/form.html.twig', [
            'menu_path' => 'ict_location_list',
            'breadcrumb' => [
                ['fixed' => $location->getId() ?
                    (string) $location :
                    $this->get('translator')->trans('title.new', [], 'ict_location')]
            ],
            'title' => $this->get('translator')->
                trans($location->getId() ? 'title.edit' : 'title.new', [], 'ict_location'),
            'form' => $form->createView(),
            'admin' => $isAdmin,
            'user' => $location
        ]);
    }

    /**
     * @Route("/listar/{page}", name="ict_location_list", requirements={"page" = "\d+"},
     *     defaults={"page" = "1"}, methods={"GET"})
     */
    public function listAction($page, Request $request, UserExtensionService $userExtensionService)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $organization = $userExtensionService->getCurrentOrganization();
        $isAdmin = $this->isGranted(OrganizationVoter::MANAGE, $organization);

        $queryBuilder
            ->select('l')
            ->addSelect('COUNT(e)')
            ->addSelect('COUNT(e.unavailableSince) AS unavailable_count')
            ->addSelect('COUNT(e.beingRepairedSince) AS being_repaired_count')
            ->from('AppBundle:ICT\Location', 'l')
            ->leftJoin('AppBundle:ICT\\Element', 'e', 'WITH', 'e.location = l')
            ->groupBy('l')
            ->orderBy('l.name');

        $q = $request->get('q', null);
        $f = $request->get('f', 0);
        if ($q) {
            $queryBuilder
                ->orWhere('l.name LIKE :tq')
                ->orWhere('l.additionalData LIKE :tq')
                ->orWhere('l.description LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }
        if ($f) {
            switch ($f) {
                case 1:
                    $queryBuilder->andHaving('unavailable_count > 0');
                    break;
                case 2:
                    $queryBuilder->andHaving('being_repaired_count > 0');
                    break;
            }
        }
        $queryBuilder
            ->andWhere('l.organization = :organization')
            ->setParameter('organization', $organization);

        if (false === $isAdmin) {
            $queryBuilder
                ->andWhere('l.hidden = false');
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'ict_location');

        return $this->render('ict/location/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'f' => $f,
            'admin' => $isAdmin,
            'domain' => 'ict_location'
        ]);
    }

    /**
     * @Route("/operacion", name="ict_location_operation", methods={"POST"})
     */
    public function operationAction(Request $request, UserExtensionService $userExtensionService)
    {
        $organization = $userExtensionService->getCurrentOrganization();

        list($redirect, $locations) = $this->processOperations($request, $organization);

        if ($redirect) {
            return $this->redirectToRoute('ict_location_list');
        }

        return $this->render('ict/location/delete.html.twig', [
            'menu_path' => 'ict_location_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'ict_location')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'ict_location'),
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
            ->update('AppBundle:ICT\Element', 'e')
            ->set('e.location', ':location')
            ->where('e.location IN (:items)')
            ->setParameter('location', null)
            ->setParameter('items', $locations)
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->update('AppBundle:ICT\Location', 'l')
            ->set('l.parent', ':parent')
            ->where('l.parent IN (:items)')
            ->setParameter('parent', null)
            ->setParameter('items', $locations)
            ->getQuery()
            ->execute();

        /* Finalmente eliminamos las dependencias */
        $em->createQueryBuilder()
            ->delete('AppBundle:ICT\Location', 'l')
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
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'ict_location'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'ict_location'));
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
            $locations = $em->getRepository('AppBundle:ICT\Location')->
                findInListByIdAndOrganization($items, $organization);
            $redirect = $this->processRemoveLocations($request, $locations, $em);
        }
        return array($redirect, $locations);
    }
}
