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

namespace App\Controller\Organization\Import;

use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MenuController extends AbstractController
{
    /**
     * @Route("/centro/importar", name="organization_import", methods={"GET"})
     */
    public function indexAction(UserExtensionService $userExtensionService)
    {
        $this->denyAccessUnlessGranted(OrganizationVoter::MANAGE, $userExtensionService->getCurrentOrganization());
        return $this->render(
            'default/index.html.twig',
            [
                'menu' => true
            ]
        );
    }
}
