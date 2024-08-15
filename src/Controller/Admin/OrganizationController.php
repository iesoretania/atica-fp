<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use PagerFanta\Exception\OutOfRangeCurrentPageException;
use Pagerfanta\Pagerfanta;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route(path: '/admin/organizaciones')]
class OrganizationController extends AbstractController
{
    #[Route(path: '/nueva', name: 'admin_organization_form_new', methods: ['GET', 'POST'])]
    #[Route(path: '/{id}', name: 'admin_organization_form_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OrganizationRepository $organizationRepository,
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Organization $organization = null
    ): Response {
        $em = $managerRegistry->getManager();

        if (!$organization instanceof Organization) {
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
            } catch (\Exception) {
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

    #[Route(path: '/listar/{page}', name: 'admin_organization_list', requirements: ['page' => '\d+'], defaults: ['page' => '1'], methods: ['GET'])]
    public function list(
        TranslatorInterface $translator,
        ManagerRegistry $managerRegistry,
        Request $request,
        int $page = 1
    ): Response
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $managerRegistry->getManager()->createQueryBuilder();

        $queryBuilder
            ->select('o')
            ->from(Organization::class, 'o')
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
        } catch (OutOfRangeCurrentPageException) {
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

    #[Route(path: '/operacion', name: 'admin_organization_operation', methods: ['POST'])]
    public function operation(
        Request $request,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry,
        TranslatorInterface $translator
    ): Response {
        [$redirect, $organizations] = $this->processOperations($request, $translator, $userExtensionService, $managerRegistry);

        if ($redirect) {
            return $this->redirectToRoute('admin_organization_list');
        }

        return $this->render('admin/organization/delete.html.twig', [
            'menu_path' => 'admin_organization_list',
            'breadcrumb' => [['fixed' => $translator->trans('title.delete', [], 'organization')]],
            'title' => $translator->trans('title.delete', [], 'organization'),
            'selected_organizations' => $organizations
        ]);
    }

    /**
     * Borrar los datos de las organizaciones pasados como parámetros
     *
     * @param Organization[] $organizations
     */
    private function deleteOrganizations($organizations, ObjectManager $em): void
    {
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
     * @param $organizations
     * @return bool
     */
    private function processRemoveOrganizations(Request $request, TranslatorInterface $translator, $organizations, ObjectManager $em)
    {
        $redirect = false;
        if ($request->get('confirm', '') === 'ok') {
            try {
                $this->deleteOrganizations($organizations, $em);
                $em->flush();
                $this->addFlash('success', $translator->trans('message.deleted', [], 'organization'));
            } catch (\Exception) {
                $this->addFlash('error', $translator->trans('message.delete_error', [], 'organization'));
            }
            $redirect = true;
        }
        return $redirect;
    }

    private function processSwitchOrganization(
        Request $request,
        TranslatorInterface $translator,
        ObjectManager $em
    ): bool {
        $organization = $em->getRepository(Organization::class)->find($request->request->get('switch', null));
        if ($organization !== null) {
            $request->getSession()->set('organization_id', $organization->getId());
            $this->addFlash(
                'success',
                $translator->
                trans('message.switched', ['%name%' => $organization->getName()], 'organization')
            );
            return true;
        }
        return false;
    }

    private function processOperations(
        Request $request,
        TranslatorInterface $translator,
        UserExtensionService $userExtensionService,
        ManagerRegistry $managerRegistry
    ): array {
        $em = $managerRegistry->getManager();

        $redirect = false;
        if ($request->request->has('switch')) {
            $redirect = $this->processSwitchOrganization($request, $translator, $em);
        }

        $items = $request->request->get('organizations', []);
        if ((is_countable($items) ? count($items) : 0) === 0) {
            $redirect = true;
        }

        $organizations = [];
        if (!$redirect) {
            $organizations = $em->getRepository(Organization::class)->
                findAllInListByIdButCurrent($items, $userExtensionService->getCurrentOrganization());
            $redirect = $this->processRemoveOrganizations($request, $translator, $organizations, $em);
        }
        return [$redirect, $organizations];
    }
}
