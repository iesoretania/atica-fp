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
use AppBundle\Security\WPT\WPTOrganizationVoter;
use AppBundle\Service\MenuBuilderInterface;
use AppBundle\Service\UserExtensionService;
use Symfony\Component\Security\Core\Security;

class WPTMenu implements MenuBuilderInterface
{
    private $userExtension;

    /** @var Security */
    private $security;

    public function __construct(UserExtensionService $userExtension, Security $security)
    {
        $this->userExtension = $userExtension;
        $this->security = $security;
    }

    /**
     * @return array|null
     */
    public function getMenuStructure()
    {
        $organization = $this->userExtension->getCurrentOrganization();

        $root = [];

        if ($this->security->isGranted(WPTOrganizationVoter::WPT_ACCESS, $organization)) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('workplace_training')
                ->setRouteName('workplace_training')
                ->setCaption('menu.workplace_training')
                ->setDescription('menu.workplace_training.detail')
                ->setIcon('store-alt')
                ->setPriority(5000);

            $root[] = $menu1;

            if ($this->security->isGranted(WPTOrganizationVoter::WPT_MANAGE, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('workplace_training_shift')
                    ->setRouteName('workplace_training_shift_list')
                    ->setCaption('menu.workplace_training.shift')
                    ->setDescription('menu.workplace_training.shift.detail')
                    ->setIcon('folder-open')
                    ->setPriority(1000);

                $menu1->addChild($menu2);
            }

            $menu2 = new MenuItem();
            $menu2
                ->setName('workplace_training_tracking')
                ->setRouteName('workplace_training_tracking_list')
                ->setCaption('menu.workplace_training.tracking')
                ->setDescription('menu.workplace_training.tracking.detail')
                ->setIcon('user-clock')
                ->setPriority(2000);

            $menu1->addChild($menu2);

            if ($this->security->isGranted(WPTOrganizationVoter::WPT_FILL_REPORT, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('workplace_training_final_report')
                    ->setRouteName('workplace_training_final_report_list')
                    ->setCaption('menu.workplace_training.final_report')
                    ->setDescription('menu.workplace_training.final_report.detail')
                    ->setIcon('file-signature')
                    ->setPriority(3000);

                $menu1->addChild($menu2);

                if ($this->security->isGranted(WPTOrganizationVoter::WPT_ACCESS_VISIT, $organization)) {
                    $menu2 = new MenuItem();
                    $menu2
                        ->setName('workplace_training_visit')
                        ->setRouteName('workplace_training_visit_list')
                        ->setCaption('menu.workplace_training.visit')
                        ->setDescription('menu.workplace_training.visit.detail')
                        ->setIcon('car-side')
                        ->setPriority(4000);

                    $menu1->addChild($menu2);

                    $menu2 = new MenuItem();
                    $menu2
                        ->setName('workplace_training_travel_expense')
                        ->setRouteName('workplace_training_travel_expense_teacher_list')
                        ->setCaption('menu.workplace_training.travel_expense')
                        ->setDescription('menu.workplace_training.travel_expense.detail')
                        ->setIcon('road')
                        ->setPriority(5000);

                    $menu1->addChild($menu2);
                }
            }
        }

        return $root;
    }
}
