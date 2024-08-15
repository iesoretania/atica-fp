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

namespace App\Service\Menu;

use App\Menu\MenuItem;
use App\Security\Edu\EduOrganizationVoter;
use App\Security\OrganizationVoter;
use App\Service\MenuBuilderInterface;
use App\Service\UserExtensionService;
use Symfony\Bundle\SecurityBundle\Security;

class EduMenu implements MenuBuilderInterface
{
    public function __construct(private readonly UserExtensionService $userExtension, private readonly Security $security)
    {
    }

    public function getMenuStructure(): array
    {
        $organization = $this->userExtension->getCurrentOrganization();
        $isLocalAdministrator = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isFinancialManager = $this->security->isGranted(EduOrganizationVoter::EDU_FINANCIAL_MANAGER, $organization);

        $root = [];

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

        if ($isLocalAdministrator || $isFinancialManager) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('organization')
                ->setRouteName('organization')
                ->setCaption('menu.organization')
                ->setDescription('menu.organization.detail')
                ->setIcon('school')
                ->setPriority(8000);

            $root[] = $menu1;

            if ($isLocalAdministrator) {


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
                    ->setName('organization_student_enrollment')
                    ->setRouteName('organization_student_enrollment_list')
                    ->setCaption('menu.organization.student_enrollment')
                    ->setDescription('menu.organization.student_enrollment.detail')
                    ->setIcon('child')
                    ->setPriority(5);

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
                    ->setName('organization_contact_method')
                    ->setRouteName('organization_contact_method_list')
                    ->setCaption('menu.organization.contact_method')
                    ->setDescription('menu.organization.contact_method.detail')
                    ->setIcon('broadcast-tower')
                    ->setPriority(11000);

                $menu1->addChild($menu2);

                $menu2 = new MenuItem();
                $menu2
                    ->setName('organization_role')
                    ->setRouteName('organization_role')
                    ->setCaption('menu.organization.role')
                    ->setDescription('menu.organization.role.detail')
                    ->setIcon('user-tie')
                    ->setPriority(12000);

                $menu1->addChild($menu2);

                $menu2 = new MenuItem();
                $menu2
                    ->setName('organization_survey')
                    ->setRouteName('organization_survey_list')
                    ->setCaption('menu.organization.survey')
                    ->setDescription('menu.organization.survey.detail')
                    ->setIcon('chart-pie')
                    ->setPriority(13000);

                $menu1->addChild($menu2);

                $menu2 = new MenuItem();
                $menu2
                    ->setName('organization_report_template')
                    ->setRouteName('organization_report_template_list')
                    ->setCaption('menu.organization.report_template')
                    ->setDescription('menu.organization.report_template.detail')
                    ->setIcon('file')
                    ->setPriority(13500);

                $menu1->addChild($menu2);
            }

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_travel_route')
                ->setRouteName('organization_travel_route_list')
                ->setCaption('menu.organization.travel_route')
                ->setDescription('menu.organization.travel_route.detail')
                ->setIcon('map')
                ->setPriority(13700);

            $menu1->addChild($menu2);

            if ($isLocalAdministrator) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('organization_import')
                    ->setRouteName('organization_import')
                    ->setCaption('menu.organization.import')
                    ->setDescription('menu.organization.import.detail')
                    ->setIcon('download')
                    ->setPriority(14000);

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
                    ->setName('organization_import_subject_criteria')
                    ->setRouteName('organization_import_criteria_form')
                    ->setCaption('menu.organization.import.criteria')
                    ->setDescription('menu.organization.import.criteria.detail')
                    ->setIcon('clipboard-list')
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
                    ->setName('organization_import_login_student')
                    ->setRouteName('organization_import_student_login_form')
                    ->setCaption('menu.organization.import.student_login')
                    ->setDescription('menu.organization.import.student_login.detail')
                    ->setIcon('address-book')
                    ->setPriority(40);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('organization_import_department')
                    ->setRouteName('organization_import_department_form')
                    ->setCaption('menu.organization.import.department')
                    ->setDescription('menu.organization.import.department.detail')
                    ->setIcon('users')
                    ->setPriority(50);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('organization_import_non_working_day')
                    ->setRouteName('organization_import_non_working_day_form')
                    ->setCaption('menu.organization.import.non_working_day')
                    ->setDescription('menu.organization.import.non_working_day.detail')
                    ->setIcon('calendar-times')
                    ->setPriority(60);

                $menu2->addChild($menu3);
            }
        }

        return $root;
    }
}
