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

namespace AppBundle\Service\Menu;

use AppBundle\Menu\MenuItem;
use AppBundle\Service\MenuBuilderInterface;
use AppBundle\Service\UserExtensionService;

class CoreMenu implements MenuBuilderInterface
{
    private $userExtension;

    public function __construct(UserExtensionService $userExtension)
    {
        $this->userExtension = $userExtension;
    }

    /**
     * @return array|null
     */
    public function getMenuStructure()
    {
        $root = [];
        $isGlobalAdministrator = $this->userExtension->isUserGlobalAdministrator();

        if ($isGlobalAdministrator) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('admin')
                ->setRouteName('admin')
                ->setCaption('menu.admin')
                ->setDescription('menu.admin.detail')
                ->setIcon('tools')
                ->setPriority(9000);

            $root[] = $menu1;

            $menu2 = new MenuItem();
            $menu2
                ->setName('admin_user')
                ->setRouteName('admin_user_list')
                ->setCaption('menu.admin.user')
                ->setDescription('menu.admin.user.detail')
                ->setIcon('id-badge');

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('admin_organization')
                ->setRouteName('admin_organization_list')
                ->setCaption('menu.admin.organization')
                ->setDescription('menu.admin.organization.detail')
                ->setIcon('building');

            $menu1->addChild($menu2);
        }

        $menu = new MenuItem();
        $menu
            ->setName('personal_data')
            ->setRouteName('personal_data')
            ->setCaption('menu.personal_data')
            ->setDescription('menu.personal_data.detail')
            ->setIcon('clipboard-list')
            ->setPriority(9999);

        $root[] = $menu;

        return $root;
    }
}
