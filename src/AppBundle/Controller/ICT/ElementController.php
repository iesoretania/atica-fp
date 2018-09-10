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

use AppBundle\Entity\ICT\Element;
use AppBundle\Entity\Organization;
use AppBundle\Form\Type\ICT\ElementType;
use AppBundle\Security\ICT\ElementVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/tic/equipamiento")
 */
class ElementController extends Controller
{
    /**
     * @Route("/nuevo", name="ict_element_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="ict_element_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(UserExtensionService $userExtensionService, Request $request, Element $element = null)
    {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);
        if ($element) {
            $this->denyAccessUnlessGranted(ElementVoter::MANAGE, $element);
        }

        $em = $this->getDoctrine()->getManager();

        if (null === $element) {
            $element = new Element();
            $element
                ->setOrganization($organization)
                ->setListedOn(new \DateTime());
            $em->persist($element);
        }

        $form = $this->createForm(ElementType::class, $element, [
            'new' => $element->getId() === null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'ict_element'));
                return $this->redirectToRoute('ict_element_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.save_error', [], 'ict_element'));
            }
        }

        return $this->render('ict/element/form.html.twig', [
            'menu_path' => 'ict_element_list',
            'breadcrumb' => [['fixed' => $element->getId() ? $element->getName() : $this->get('translator')->trans('title.new', [], 'ict_element')]],
            'title' => $this->get('translator')->trans($element->getId() ? 'title.edit' : 'title.new', [], 'ict_element'),
            'form' => $form->createView()
        ]);
    }


    /**
     * @Route("/listar/{page}", name="ict_element_list", requirements={"page" = "\d+"}, defaults={"page" = "1"}, methods={"GET"})
     */
    public function listAction($page, Request $request, UserExtensionService $userExtensionService)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('e')
            ->from('AppBundle:ICT\Element', 'e')
            ->orderBy('e.name')
            ->addOrderBy('e.description')
            ->addOrderBy('e.serialNumber');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->orWhere('e.name LIKE :tq')
                ->orWhere('e.description LIKE :tq')
                ->orWhere('e.serialNumber LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('e.organization = :organization')
            ->setParameter('organization', $userExtensionService->getCurrentOrganization());

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'ict_element');

        return $this->render('ict/element/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'ict_element'
        ]);
    }

    /**
     * @Route("/operacion", name="ict_element_operation", methods={"POST"})
     */
    public function operationAction(Request $request, UserExtensionService $userExtensionService)
    {
        $organization = $userExtensionService->getCurrentOrganization();

        list($redirect, $items) = $this->processOperations($request, $organization);

        if ($redirect) {
            return $this->redirectToRoute('ict_element_list');
        }

        return $this->render('ict/element/delete.html.twig', [
            'menu_path' => 'ict_location_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'location')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'location'),
            'items' => $items
        ]);
    }

    /**
     * Borrar los datos de los elementos pasados como parámetros
     *
     * @param Element[] $items
     */
    private function deleteItems($items)
    {
        $em = $this->getDoctrine()->getManager();


        /* Eliminar tickets asociados a los elementosv*/
        $em->createQueryBuilder()
            ->delete('AppBundle:ICT\\Ticket', 't')
            ->where('t.element IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();

        /* Finalmente eliminamos los elementos */
        $em->createQueryBuilder()
            ->delete('AppBundle:ICT\\Element', 'e')
            ->where('e IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Request $request
     * @param $items
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @return bool
     */
    private function processRemoveItems(Request $request, $items, $em)
    {
        $redirect = false;
        if ($request->get('confirm', '') === 'ok') {
            try {
                $this->deleteItems($items);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'ict_element'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'ict_element'));
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

        $items = $request->request->get('items', []);
        if (count($items) === 0) {
            $redirect = true;
        }

        $itemToRemove = [];
        if (!$redirect) {
            $itemToRemove = $em->getRepository('AppBundle:ICT\Element')->findInListByIdAndOrganization($items, $organization);
            $redirect = $this->processRemoveItems($request, $itemToRemove, $em);
        }
        return array($redirect, $itemToRemove);
    }
}
