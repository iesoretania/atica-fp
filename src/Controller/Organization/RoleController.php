<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

use App\Form\Type\Edu\RoleAssignType;
use App\Repository\RoleRepository;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoleController extends AbstractController
{
    /**
     * @Route("/centro/rol", name="organization_role", methods={"GET", "POST"})
     */
    public function indexAction(
        Request $request,
        UserExtensionService $userExtensionService,
        RoleRepository $roleRepository,
        TranslatorInterface $translator
    ) {
        $organization = $userExtensionService->getCurrentOrganization();
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $organization);

        $roleData = [];

        $form = $this->createForm(RoleAssignType::class, $roleData);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $roleRepository->updateOrganizationRoles($organization, $form->getData());
                $this->getDoctrine()->getManager()->flush();
                $this->addFlash('success', $translator->trans('message.saved', [], 'role'));
                return $this->redirectToRoute('organization');
            } catch (\Exception $e) {
                $this->addFlash('error',$translator->trans('message.save_error', [], 'role'));
            }
        }

        return $this->render('organization/role/form.html.twig', [
            'menu_path' => 'organization_role',
            'breadcrumb' => [],
            'title' => $translator->trans('title.roles', [], 'role'),
            'form' => $form->createView()
        ]);
    }
}
