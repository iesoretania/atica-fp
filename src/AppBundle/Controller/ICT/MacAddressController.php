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

use AppBundle\Entity\ICT\MacAddress;
use AppBundle\Entity\Organization;
use AppBundle\Form\Type\ICT\MacAddressType;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/wifi")
 */
class MacAddressController extends Controller
{
    /**
     * @Route("/nuevo", name="ict_mac_address_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="ict_mac_address_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        Request $request,
        MacAddress $macAddress = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $macAddress) {
            $macAddress = new MacAddress();
            $macAddress
                ->setOrganization($organization)
                ->setPerson($this->getUser()->getPerson())
                ->setCreatedOn(new \DateTime());
            $em->persist($macAddress);
        }

        $form = $this->createForm(MacAddressType::class, $macAddress, [
            'new' => $macAddress->getId() === null,
            'admin' => true
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'ict_mac_address'));
                return $this->redirectToRoute('ict_mac_address_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.save_error', [], 'ict_mac_address'));
            }
        }

        return $this->render('ict/mac_address/form.html.twig', [
            'menu_path' => 'ict_mac_address_list',
            'breadcrumb' => [
                ['fixed' => $macAddress->getId() ?
                    $macAddress->getAddress() :
                    $this->get('translator')->trans('title.new', [], 'ict_mac_address')]
            ],
            'title' => $this->get('translator')->
                trans($macAddress->getId() ? 'title.edit' : 'title.new', [], 'ict_mac_address'),
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/listar/{page}", name="ict_mac_address_list", requirements={"page" = "\d+"},
     *     defaults={"page" = "1"}, methods={"GET"})
     */
    public function listAction($page, Request $request, UserExtensionService $userExtensionService)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('m')
            ->from('AppBundle:ICT\MacAddress', 'm')
            ->join('m.person', 'p')
            ->orderBy('m.createdOn', 'DESC')
            ->addOrderBy('m.id', 'DESC');

        $q = $request->get('q', null);
        $f = $request->get('f', 0);
        if ($q) {
            $queryBuilder
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('m.description LIKE :tq')
                ->orWhere('m.address LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }

        if ($f) {
            switch ($f) {
                case 1:
                    $queryBuilder
                        ->andWhere('m.registeredOn IS NULL')
                        ->andWhere('m.unRegisteredOn IS NULL');
                    break;
                case 2:
                    $queryBuilder
                        ->andWhere('m.registeredOn IS NOT NULL')
                        ->andWhere('m.unRegisteredOn IS NULL');
                    break;
                case 3:
                    $queryBuilder
                        ->andWhere('m.unRegisteredOn IS NOT NULL');
                    break;
            }
        }

        $queryBuilder
            ->andWhere('m.organization = :organization')
            ->setParameter('organization', $userExtensionService->getCurrentOrganization());

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'ict_mac_address');

        return $this->render('ict/mac_address/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'f' => $f,
            'domain' => 'ict_mac_address'
        ]);
    }

    /**
     * @Route("/operacion", name="ict_mac_address_operation", methods={"POST"})
     */
    public function operationAction(Request $request, UserExtensionService $userExtensionService)
    {
        $organization = $userExtensionService->getCurrentOrganization();

        list($redirect, $items) = $this->processOperations($request, $organization);

        if ($redirect) {
            return $this->redirectToRoute('ict_mac_address_list');
        }

        return $this->render('ict/mac_address/delete.html.twig', [
            'menu_path' => 'ict_mac_address_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'ict_mac_address')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'ict_mac_address'),
            'items' => $items
        ]);
    }

    /**
     * Borrar los datos de los dispositivos pasados como parámetros
     *
     * @param MacAddress[] $items
     */
    private function deleteItems($items)
    {
        $em = $this->getDoctrine()->getManager();

        /* Finalmente eliminamos los elementos */
        $em->createQueryBuilder()
            ->delete('AppBundle:ICT\MacAddress', 'm')
            ->where('m IN (:items)')
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
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'ict_mac_address'));
            } catch (\Exception $e) {
                $this->addFlash(
                    'error',
                    $this->get('translator')->trans('message.delete_error', [], 'ict_mac_address')
                );
            }
            $redirect = true;
        }
        return $redirect;
    }

    /**
     * Dar de alta los dispositivos pasados como parámetro
     *
     * @param MacAddress[] $items
     */
    private function registerItems($items)
    {
        $em = $this->getDoctrine()->getManager();

        /* Finalmente eliminamos los elementos */
        $em->createQueryBuilder()
            ->update('AppBundle:ICT\MacAddress', 'm')
            ->set('m.registeredOn', ':now')
            ->set('m.unRegisteredOn', ':none')
            ->where('m IN (:items)')
            ->setParameter('items', $items)
            ->setParameter('now', new \DateTime())
            ->setParameter('none', null)
            ->getQuery()
            ->execute();
    }

    /**
     * Dar de baja los dispositivos pasados como parámetro
     *
     * @param MacAddress[] $items
     */
    private function unRegisterItems($items)
    {
        $em = $this->getDoctrine()->getManager();

        /* Finalmente eliminamos los elementos */
        $em->createQueryBuilder()
            ->update('AppBundle:ICT\MacAddress', 'm')
            ->set('m.unRegisteredOn', ':now')
            ->where('m IN (:items)')
            ->setParameter('items', $items)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->execute();
    }

    /**
     * @param Request $request
     * @param $items
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @param bool $kind
     * @return bool
     */
    private function processRegisterItems(Request $request, $items, $em, $kind)
    {
        try {
            if ($kind) {
                $this->registerItems($items);
            } else {
                $this->unRegisterItems($items);
            }
            $em->flush();
            $this->addFlash(
                'success',
                $this->get('translator')->trans(
                    $kind ? 'message.registered' : 'message.unregistered',
                    [],
                    'ict_mac_address'
                )
            );
        } catch (\Exception $e) {
            throw($e);
            $this->addFlash(
                'error',
                $this->get('translator')->trans(
                    $kind ? 'message.register_error' : 'message.unregister_error',
                    [],
                    'ict_mac_address'
                )
            );
        }
        return true;
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

        $selectedItems = [];
        if (!$redirect) {
            $selectedItems = $em->getRepository('AppBundle:ICT\MacAddress')->
                findInListByIdAndOrganization($items, $organization);
            if ($request->get('delete', null) === '') {
                $redirect = $this->processRemoveItems($request, $selectedItems, $em);
            } elseif ($request->get('register', null) === '') {
                $redirect = $this->processRegisterItems($request, $selectedItems, $em, true);
            } elseif ($request->get('unregister', null) === '') {
                $redirect = $this->processRegisterItems($request, $selectedItems, $em, false);
            }
        }
        return array($redirect, $selectedItems);
    }
}
