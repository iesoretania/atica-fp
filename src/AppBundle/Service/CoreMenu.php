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
                ->setIcon('building');

            $menu1->addChild($menu2);
        }

        if ($isLocalAdministrator) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('organization')
                ->setRouteName('organization')
                ->setCaption('menu.organization')
                ->setDescription('menu.organization.detail')
                ->setIcon('school')
                ->setPriority(8000);

            $root[] = $menu1;

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_teacher')
                ->setRouteName('organization_teacher_list')
                ->setCaption('menu.organization.teacher')
                ->setDescription('menu.organization.teacher.detail')
                ->setIcon('chalkboard-teacher')
                ->setPriority(0);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_group')
                ->setRouteName('organization_group_list')
                ->setCaption('menu.organization.group')
                ->setDescription('menu.organization.group.detail')
                ->setIcon('chalkboard')
                ->setPriority(10);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_grade')
                ->setRouteName('organization_grade_list')
                ->setCaption('menu.organization.grade')
                ->setDescription('menu.organization.grade.detail')
                ->setIcon('columns')
                ->setPriority(20);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_training')
                ->setRouteName('organization_training_list')
                ->setCaption('menu.organization.training')
                ->setDescription('menu.organization.training.detail')
                ->setIcon('graduation-cap')
                ->setPriority(30);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_subject')
                ->setRouteName('organization_subject_list')
                ->setCaption('menu.organization.subject')
                ->setDescription('menu.organization.subject.detail')
                ->setIcon('book-open')
                ->setPriority(40);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_department')
                ->setRouteName('organization_department_list')
                ->setCaption('menu.organization.department')
                ->setDescription('menu.organization.department.detail')
                ->setIcon('users')
                ->setPriority(50);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_academic_year')
                ->setRouteName('organization_academic_year_list')
                ->setCaption('menu.organization.academic_year')
                ->setDescription('menu.organization.academic_year.detail')
                ->setIcon('calendar-alt')
                ->setPriority(9000);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_import')
                ->setRouteName('organization_import')
                ->setCaption('menu.organization.import')
                ->setDescription('menu.organization.import.detail')
                ->setIcon('download')
                ->setPriority(10000);

            $menu1->addChild($menu2);

            $menu3 = new MenuItem();
            $menu3
                ->setName('organization_import_teacher')
                ->setRouteName('organization_import_teacher_form')
                ->setCaption('menu.organization.import.teacher')
                ->setDescription('menu.organization.import.teacher.detail')
                ->setIcon('chalkboard-teacher')
                ->setPriority(0);

            $menu2->addChild($menu3);

            $menu3 = new MenuItem();
            $menu3
                ->setName('organization_import_group')
                ->setRouteName('organization_import_group_form')
                ->setCaption('menu.organization.import.group')
                ->setDescription('menu.organization.import.group.detail')
                ->setIcon('chalkboard')
                ->setPriority(10);

            $menu2->addChild($menu3);

            $menu3 = new MenuItem();
            $menu3
                ->setName('organization_import_subject')
                ->setRouteName('organization_import_subject_form')
                ->setCaption('menu.organization.import.subject')
                ->setDescription('menu.organization.import.subject.detail')
                ->setIcon('book-open')
                ->setPriority(20);

            $menu2->addChild($menu3);

            $menu3 = new MenuItem();
            $menu3

                ->setName('organization_import_student')
                ->setRouteName('organization_import_student_form')
                ->setCaption('menu.organization.import.student')
                ->setDescription('menu.organization.import.student.detail')
                ->setIcon('child')
                ->setPriority(30);

            $menu2->addChild($menu3);

            $menu3 = new MenuItem();
            $menu3

                ->setName('organization_import_department')
                ->setRouteName('organization_import_department_form')
                ->setCaption('menu.organization.import.department')
                ->setDescription('menu.organization.import.department.detail')
                ->setIcon('users')
                ->setPriority(40);

            $menu2->addChild($menu3);
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
