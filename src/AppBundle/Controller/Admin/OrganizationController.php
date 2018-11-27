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

namespace AppBundle\Controller\Admin;

use AppBundle\Entity\Organization;
use AppBundle\Form\Type\OrganizationType;
use AppBundle\Repository\OrganizationRepository;
use AppBundle\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route("/admin/organizaciones")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class OrganizationController extends Controller
{
    /**
     * @Route("/nueva", name="admin_organization_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="admin_organization_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(Request $request, OrganizationRepository $organizationRepository, Organization $organization = null)
    {
        $em = $this->getDoctrine()->getManager();

        if (null === $organization) {
            $organization = $organizationRepository->createEducationalOrganization();
        }

        $form = $this->createForm(OrganizationType::class, $organization, [
            'new' => $organization->getId() === null
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.saved', [], 'organization'));
                return $this->redirectToRoute('admin_organization_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.save_error', [], 'organization'));
            }
        }

        return $this->render('admin/organization/form.html.twig', [
            'menu_path' => 'admin_organization_list',
            'breadcrumb' => [
                ['fixed' => $organization->getId() ?
                    (string) $organization :
                    $this->get('translator')->trans('title.new', [], 'organization')]
            ],
            'title' => $this->get('translator')->
                trans($organization->getId() ? 'title.edit' : 'title.new', [], 'organization'),
            'form' => $form->createView(),
            'user' => $organization
        ]);
    }

    /**
     * @Route("/listar/{page}", name="admin_organization_list", requirements={"page" = "\d+"},
     *     defaults={"page" = "1"}, methods={"GET"})
     */
    public function listAction($page, Request $request)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('o')
            ->from('AppBundle:Organization', 'o')
            ->orderBy('o.name');

        $q = $request->get('q', null);
        if ($q) {
            $queryBuilder
                ->where('o.id = :q')
                ->orWhere('o.name LIKE :tq')
                ->orWhere('o.code LIKE :tq')
                ->orWhere('o.emailAddress LIKE :tq')
                ->orWhere('o.phoneNumber LIKE :tq')
                ->orWhere('o.city LIKE :tq')
                ->setParameter('tq', '%' . $q . '%')
                ->setParameter('q', $q);
        }

        $adapter = new DoctrineORMAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        $pager
            ->setMaxPerPage($this->getParameter('page.size'))
            ->setCurrentPage($q ? 1 : $page);

        $title = $this->get('translator')->trans('title.list', [], 'organization');

        return $this->render('admin/organization/list.html.twig', [
            'title' => $title,
            'pager' => $pager,
            'q' => $q,
            'domain' => 'organization'
        ]);
    }

    /**
     * @Route("/operacion", name="admin_organization_operation", methods={"POST"})
     */
    public function operationAction(Request $request)
    {
        list($redirect, $organizations) = $this->processOperations($request);

        if ($redirect) {
            return $this->redirectToRoute('admin_organization_list');
        }

        return $this->render('admin/organization/delete.html.twig', [
            'menu_path' => 'admin_organization_list',
            'breadcrumb' => [['fixed' => $this->get('translator')->trans('title.delete', [], 'organization')]],
            'title' => $this->get('translator')->trans('title.delete', [], 'organization'),
            'organizations' => $organizations
        ]);
    }

    /**
     * Borrar los datos de las organizaciones pasados como parámetros
     *
     * @param Organization[] $organizations
     */
    private function deleteOrganizations($organizations)
    {
        $em = $this->getDoctrine()->getManager();

        /* Borrar primero las pertenencias */
        $em->createQueryBuilder()
            ->delete('AppBundle:Membership', 'm')
            ->where('m.organization IN (:items)')
            ->setParameter('items', $organizations)
            ->getQuery()
            ->execute();

        /* Finalmente las organizaciones */
        $em->createQueryBuilder()
            ->delete('AppBundle:Organization', 'o')
            ->where('o IN (:items)')
            ->setParameter('items', $organizations)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Request $request
     * @param $organizations
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @return bool
     */
    private function processRemoveOrganizations(Request $request, $organizations, $em)
    {
        $redirect = false;
        if ($request->get('confirm', '') === 'ok') {
            try {
                $this->deleteOrganizations($organizations);
                $em->flush();
                $this->addFlash('success', $this->get('translator')->trans('message.deleted', [], 'organization'));
            } catch (\Exception $e) {
                $this->addFlash('error', $this->get('translator')->trans('message.delete_error', [], 'organization'));
            }
            $redirect = true;
        }
        return $redirect;
    }

    /**
     * @param Request $request
     * @param \Doctrine\Common\Persistence\ObjectManager $em
     * @return bool
     */
    private function processSwitchOrganization(Request $request, $em)
    {
        $organization = $em->getRepository('AppBundle:Organization')->find($request->request->get('switch', null));
        if ($organization) {
            $this->get('session')->set('organization_id', $organization->getId());
            $this->addFlash('success', $this->get('translator')->
                trans('message.switched', ['%name%' => $organization->getName()], 'organization'));
            return true;
        }
        return false;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function processOperations(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $redirect = false;
        if ($request->request->has('switch')) {
            $redirect = $this->processSwitchOrganization($request, $em);
        }

        $items = $request->request->get('organizations', []);
        if (count($items) === 0) {
            $redirect = true;
        }

        $organizations = [];
        if (!$redirect) {
            $organizations = $em->getRepository('AppBundle:Organization')->
                findAllInListByIdButCurrent($items, $this->get(UserExtensionService::class)->getCurrentOrganization());
            $redirect = $this->processRemoveOrganizations($request, $organizations, $em);
        }
        return array($redirect, $organizations);
    }
}
