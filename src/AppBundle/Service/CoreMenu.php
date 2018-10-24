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

namespace AppBundle\Service;

use AppBundle\Menu\MenuItem;

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
        $isGlobalAdministrator = $this->userExtension->isUserGlobalAdministrator();
        $isLocalAdministrator = $this->userExtension->isUserLocalAdministrator();

        $root = [];

        if ($isGlobalAdministrator) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('admin')
                ->setRouteName('admin')
                ->setCaption('menu.admin')
                ->setDescription('menu.admin.detail')
                ->setIcon('wrench')
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
                ->setIcon('map-marker');

            $menu1->addChild($menu2);
        }

        if ($isLocalAdministrator) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('organization')
                ->setRouteName('organization')
                ->setCaption('menu.organization')
                ->setDescription('menu.organization.detail')
                ->setIcon('university')
                ->setPriority(8000);

            $root[] = $menu1;

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_import')
                ->setRouteName('organization_import')
                ->setCaption('menu.organization.import')
                ->setDescription('menu.organization.import.detail')
                ->setIcon('download');

            $menu1->addChild($menu2);

            $menu3 = new MenuItem();
            $menu3
                ->setName('organization_import_teacher')
                ->setRouteName('organization_import_teacher_form')
                ->setCaption('menu.organization.import.teacher')
                ->setDescription('menu.organization.import.teacher.detail')
                ->setIcon('graduation-cap')
                ->setPriority(0);

            $menu2->addChild($menu3);

            $menu3 = new MenuItem();
            $menu3
                ->setName('organization_import_location')
                ->setRouteName('organization_import_location_form')
                ->setCaption('menu.organization.import.location')
                ->setDescription('menu.organization.import.location.detail')
                ->setIcon('store-alt')
                ->setPriority(0);

            $menu2->addChild($menu3);
        }

        $menu2 = new MenuItem();
        $menu2
            ->setName('ict')
            ->setRouteName('ict_menu')
            ->setCaption('menu.ict')
            ->setDescription('menu.ict.detail')
            ->setIcon('laptop')
            ->setPriority(0);

        $root[] = $menu2;

        $menu = new MenuItem();
        $menu
            ->setName('ict_ticket_new')
            ->setRouteName('ict_ticket_form_new')
            ->setCaption('menu.ict.ticket_new')
            ->setDescription('menu.ict.ticket_new.detail')
            ->setIcon('exclamation-triangle')
            ->setPriority(0);

        $menu2->addChild($menu);

        $menu = new MenuItem();
        $menu
            ->setName('ict_ticket_list')
            ->setRouteName('ict_ticket_list')
            ->setCaption('menu.ict.ticket_list')
            ->setDescription('menu.ict.ticket_list.detail')
            ->setIcon('clipboard-list')
            ->setPriority(0);

        $menu2->addChild($menu);

        $menu = new MenuItem();
        $menu
            ->setName('ict_location')
            ->setRouteName('ict_location_list')
            ->setCaption('menu.ict.location')
            ->setDescription('menu.ict.location.detail')
            ->setIcon('store-alt');

        $menu2->addChild($menu);

        $menu = new MenuItem();
        $menu
            ->setName('ict_mac_address')
            ->setRouteName('ict_mac_address_list')
            ->setCaption('menu.ict.mac_address')
            ->setDescription('menu.ict.mac_address.detail')
            ->setIcon('wifi');

        $menu2->addChild($menu);
        if ($isLocalAdministrator) {
            $menu = new MenuItem();
            $menu
                ->setName('ict_ticket_inbox')
                ->setRouteName('ict_ticket_triage_list')
                ->setCaption('menu.ict.ticket_inbox')
                ->setDescription('menu.ict.ticket_inbox.detail')
                ->setIcon('inbox')
                ->setPriority(0);

            $menu2->addChild($menu);

            $menu = new MenuItem();
            $menu
                ->setName('ict_element')
                ->setRouteName('ict_element_list')
                ->setCaption('menu.ict.element')
                ->setDescription('menu.ict.element.detail')
                ->setIcon('boxes')
                ->setPriority(0);

            $menu2->addChild($menu);

            $menu = new MenuItem();
            $menu
                ->setName('ict_element_template')
                ->setRouteName('ict_element_template_form_new')
                ->setCaption('menu.ict.element_template')
                ->setDescription('menu.ict.element_template.detail')
                ->setIcon('drafting-compass')
                ->setPriority(0);

            $menu2->addChild($menu);

            $menu = new MenuItem();
            $menu
                ->setName('ict_priority')
                ->setRouteName('ict_priority_list')
                ->setCaption('menu.ict.priority')
                ->setDescription('menu.ict.priority.detail')
                ->setIcon('arrows-alt-v');

            $menu2->addChild($menu);
        }

        $menu = new MenuItem();
        $menu
            ->setName('personal_data')
            ->setRouteName('personal_data')
            ->setCaption('menu.personal_data')
            ->setDescription('menu.personal_data.detail')
            ->setIcon('cog')
            ->setPriority(9999);

        $root[] = $menu;

        $menu = new MenuItem();
        $menu
            ->setName('logout')
            ->setRouteName('logout')
            ->setCaption('menu.logout')
            ->setDescription('menu.logout.detail')
            ->setIcon('power-off')
            ->setPriority(10000);

        $root[] = $menu;
        return $root;
    }
}
