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

namespace App\Controller\Admin;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Form\Type\OrganizationType;
use App\Repository\OrganizationRepository;
use App\Service\UserExtensionService;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ObjectManager;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/admin/organizaciones")
 * @Security("is_granted('ROLE_ADMIN')")
 */
class OrganizationController extends AbstractController
{
    /**
     * @Route("/nueva", name="admin_organization_form_new", methods={"GET", "POST"})
     * @Route("/{id}", name="admin_organization_form_edit", requirements={"id" = "\d+"}, methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        OrganizationRepository $organizationRepository,
        TranslatorInterface $translator,
        Organization $organization = null
    ): Response {
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
                $this->addFlash('success', $translator->trans('message.saved', [], 'organization'));
                return $this->redirectToRoute('admin_organization_list');
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.save_error', [], 'organization'));
            }
        }

        return $this->render('admin/organization/form.html.twig', [
            'menu_path' => 'admin_organization_list',
            'breadcrumb' => [
                [
                    'fixed' => $organization->getId() ?
                        (string)$organization :
                        $translator->trans('title.new', [], 'organization')
                ]
            ],
            'title' => $translator->
            trans($organization->getId() ? 'title.edit' : 'title.new', [], 'organization'),
            'form' => $form->createView(),
            'user' => $organization
        ]);
    }

    /**
     * @Route("/listar/{page}", name="admin_organization_list", requirements={"page" = "\d+"},
     *     defaults={"page" = "1"}, methods={"GET"})
     */
    public function listAction(TranslatorInterface $translator, $page, Request $request): Response
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getDoctrine()->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('o')
            ->from('App:Organization', 'o')
            ->orderBy('o.name');

        $q = $request->get('q');
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

        $adapter = new QueryAdapter($queryBuilder, false);
        $pager = new Pagerfanta($adapter);
        try {
            $pager
                ->setMaxPerPage($this->getParameter('page.size'))
                ->setCurrentPage($page);
        } catch (OutOfRangeCurrentPageException $e) {
            $pager->setCurrentPage(1);
        }

        $title = $translator->trans('title.list', [], 'organization');

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
    public function operationAction(
        Request $request,
        UserExtensionService $userExtensionService,
        TranslatorInterface $translator
    ): Response {
        [$redirect, $organizations] = $this->processOperations($request, $translator, $userExtensionService);

        if ($redirect) {
            return $this->redirectToRoute('admin_organization_list');
        }

        return $this->render('admin/organization/delete.html.twig', [
            'menu_path' => 'admin_organization_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'organization')]],
            'title' => $translator->trans('title.delete', [], 'organization'),
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

        /* Borrar los cursos académicos */
        $em->createQueryBuilder()
            ->update(Organization::class, 'o')
            ->set('o.currentAcademicYear', ':academic_year')
            ->where('o IN (:items)')
            ->setParameter('items', $organizations)
            ->setParameter('academic_year', null)
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->delete(AcademicYear::class, 'ay')
            ->where('ay.organization IN (:items)')
            ->setParameter('items', $organizations)
            ->getQuery()
            ->execute();

        /* Finalmente las organizaciones */
        $em->createQueryBuilder()
            ->delete(Organization::class, 'o')
            ->where('o IN (:items)')
            ->setParameter('items', $organizations)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param $organizations
     * @param ObjectManager $em
     * @return bool
     */
    private function processRemoveOrganizations(Request $request, TranslatorInterface $translator, $organizations, $em)
    {
        $redirect = false;
        if ($request->get('confirm', '') === 'ok') {
            try {
                $this->deleteOrganizations($organizations);
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'organization'));
            } catch (\Exception $e) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'organization'));
            }
            $redirect = true;
        }
        return $redirect;
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param ObjectManager $em
     * @return bool
     */
    private function processSwitchOrganization(
        Request $request,
        TranslatorInterface $translator,
        ObjectManager $em
    ): bool {
        $organization = $em->getRepository('App:Organization')->find($request->request->get('switch', null));
        if ($organization !== null) {
            $this->get('session')->set('organization_id', $organization->getId());
            $this->addFlash(
                'success',
                $translator->
                trans('message.switched', ['%name%' => $organization->getName()], 'organization')
            );
            return true;
        }
        return false;
    }

    /**
     * @param Request $request
     * @return array
     */
    private function processOperations(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService
    ): array {
        $em = $this->getDoctrine()->getManager();

        $redirect = false;
        if ($request->request->has('switch')) {
            $redirect = $this->processSwitchOrganization($request, $translator, $em);
        }

        $items = $request->request->get('organizations', []);
        if ((is_array($items) || $items instanceof \Countable ? count($items) : 0) === 0) {
            $redirect = true;
        }

        $organizations = [];
        if (!$redirect) {
            $organizations = $em->getRepository('App:Organization')->
            findAllInListByIdButCurrent($items, $userExtensionService->getCurrentOrganization());
            $redirect = $this->processRemoveOrganizations($request, $translator, $organizations, $em);
        }
        return array($redirect, $organizations);
    }
}
