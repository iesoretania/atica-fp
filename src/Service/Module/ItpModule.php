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

namespace App\Service\Module;

use App\Security\ItpModule\OrganizationVoter;
use App\Security\ItpModule\OrganizationVoter as ItpOrganizationVoter;
use App\Service\UserExtensionService;
use Symfony\Bundle\SecurityBundle\Security;

class ItpModule implements ModuleBuilderInterface
{
    public function __construct(private readonly UserExtensionService $userExtension, private readonly Security $security)
    {
    }

    public function getModuleName(): ?string
    {
        return 'itp';
    }

    public function getMenuStructure(): array
    {
        $organization = $this->userExtension->getCurrentOrganization();

        $root = [];

        if ($this->security->isGranted(ItpOrganizationVoter::ITP_ACCESS_SECTION, $organization)) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('in_company_training_phase')
                ->setRouteName('in_company_training_phase')
                ->setCaption('menu.in_company_training_phase')
                ->setDescription('menu.in_company_training_phase.detail')
                ->setIcon('building-circle-check')
                ->setModule('itp')
                ->setPriority(3000);

            $root[] = $menu1;

            if ($this->security->isGranted(OrganizationVoter::ITP_MANAGER, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('in_company_training_phase_training_program')
                    ->setRouteName('in_company_training_phase_training_program_list')
                    ->setCaption('menu.in_company_training_phase.training_program')
                    ->setDescription('menu.in_company_training_phase.training_program.detail')
                    ->setIcon('folder-open')
                    ->setModule('itp')
                    ->setPriority(1000);

                $menu1->addChild($menu2);
            }
        }

        return $root;
    }
}
