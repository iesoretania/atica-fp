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

use AppBundle\Entity\ICT\Priority;
use AppBundle\Entity\Organization;
use AppBundle\Form\Type\ICT\PriorityType;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/tic/prioridad")
 */
class PriorityController extends Controller
{
    /**
     * @Route("/nueva", name="ict_priority_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="ict_priority_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        UserExtensionService $userExtensionService,
        Request $request,
        Priority $priority = null
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $em = $this->getDoctrine()->getManager();

        if (null === $priority) {
            $priority = new Priority();
            $priority
                ->setOrganization($organization)
                ->setColor('#ffffff');
            $em->persist($priority);
        }

        $form = $this->createForm(PriorityType::class, $priority, [
            'new' => $priority->getId() === null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'ict_priority'));
                return $this->redirectToRoute('ict_priority_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.save_error', [], 'ict_priority'));
            }
        }

        return $this->render('ict/priority/form.html.twig', [
            'menu_path' => 'ict_priority_list',
            'breadcrumb' => [
                ['fixed' => $priority->getId() ?
                    (string) $priority :
                    $this->get('translator')->trans('title.new', [], 'ict_priority')]
            ],
            'title' => $this->get('translator')->
                trans($priority->getId() ? 'title.edit' : 'title.new', [], 'ict_priority'),
            'form' => $form->createView(),
            'user' => $priority
        ]);
    }

    /**
     * @Route("/listar/{page}", name="ict_priority_list", requirements={"page" = "\d+"},
     *     defaults={"page" = "1"}, methods={"GET"})
     */
    public function listAction($page, Request $request, UserExtensionService $userExtensionService)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('p')
            ->from('AppBundle:ICT\Priority', 'p')
            ->orderBy('p.levelNumber', 'DESC');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->orWhere('p.name LIKE :tq')
                ->orWhere('p.description LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }

        $queryBuilder
            ->andWhere('p.organization = :organization')
            ->setParameter('organization', $userExtensionService->getCurrentOrganization());

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'ict_priority');

        return $this->render('ict/priority/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'ict_priority'
        ]);
    }

    /**
     * @Route("/operacion", name="ict_priority_operation", methods={"POST"})
     */
    public function operationAction(Request $request, UserExtensionService $userExtensionService)
    {
        $organization = $userExtensionService->getCurrentOrganization();

        list($redirect, $items) = $this->processOperations($request, $organization);

        if ($redirect) {
            return $this->redirectToRoute('ict_priority_list');
        }

        return $this->render('ict/priority/delete.html.twig', [
            'menu_path' => 'ict_priority_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'ict_priority')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'ict_priority'),
            'items' => $items
        ]);
    }

    /**
     * Borrar los datos de las dependencias pasadas como parámetros
     *
     * @param Priority[] $items
     */
    private function deleteItems($items)
    {
        $em = $this->getDoctrine()->getManager();

        /* Finalmente eliminamos los elementos */
        $em->createQueryBuilder()
            ->delete('AppBundle:ICT\Priority', 'p')
            ->where('p IN (:items)')
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
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'ict_priority'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'ict_priority'));
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

        $selectedItems = [];
        if (!$redirect) {
            $selectedItems = $em->getRepository('AppBundle:ICT\Priority')->
                findInListByIdAndOrganization($items, $organization);
            $redirect = $this->processRemoveItems($request, $selectedItems, $em);
        }
        return array($redirect, $selectedItems);
    }
}
