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
use AppBundle\Security\OrganizationVoter;
use Symfony\Component\Security\Core\Security;

class CoreMenu implements MenuBuilderInterface
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
        $isGlobalAdministrator = $this->userExtension->isUserGlobalAdministrator();
        $isLocalAdministrator = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);

        $root = [];

        if ($this->security->isGranted(OrganizationVoter::ACCESS_WORK_LINKED_TRAINING, $organization)) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('work_linked_training')
                ->setRouteName('wlt')
                ->setCaption('menu.work_linked_training')
                ->setDescription('menu.work_linked_training.detail')
                ->setIcon('city')
                ->setPriority(4000);

            $root[] = $menu1;
        }

        if ($this->security->isGranted(OrganizationVoter::ACCESS_TRAININGS, $organization)) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('training')
                ->setRouteName('training')
                ->setCaption('menu.training')
                ->setDescription('menu.training.detail')
                ->setIcon('graduation-cap')
                ->setPriority(5000);

            $root[] = $menu1;
        }

        if ($this->security->isGranted(OrganizationVoter::MANAGE_COMPANIES, $organization)) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('company')
                ->setRouteName('company')
                ->setCaption('menu.company')
                ->setDescription('menu.company.detail')
                ->setIcon('industry')
                ->setPriority(6000);

            $root[] = $menu1;
        }

        if ($isGlobalAdministrator) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('admin')
                ->setRouteName('admin')
                ->setCaption('menu.admin')
                ->setDescription('menu.admin.detail')
                ->setIcon('cogs')
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
                ->setIcon('sitemap')
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
                ->setName('organization_non_working_day')
                ->setRouteName('organization_non_working_day_list')
                ->setCaption('menu.organization.non_working_day')
                ->setDescription('menu.organization.non_working_day.detail')
                ->setIcon('calendar-times')
                ->setPriority(10000);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_role')
                ->setRouteName('organization_role')
                ->setCaption('menu.organization.role')
                ->setDescription('menu.organization.role.detail')
                ->setIcon('user-tie')
                ->setPriority(11000);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_import')
                ->setRouteName('organization_import')
                ->setCaption('menu.organization.import')
                ->setDescription('menu.organization.import.detail')
                ->setIcon('download')
                ->setPriority(12000);

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

            $menu3 = new MenuItem();
            $menu3

                ->setName('organization_import_non_working_day')
                ->setRouteName('organization_import_non_working_day_form')
                ->setCaption('menu.organization.import.non_working_day')
                ->setDescription('menu.organization.import.non_working_day.detail')
                ->setIcon('calendar-times')
                ->setPriority(50);

            $menu2->addChild($menu3);
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
