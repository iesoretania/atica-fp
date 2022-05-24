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

namespace App\Service\Menu;

use App\Menu\MenuItem;
use App\Security\WPT\WPTOrganizationVoter;
use App\Service\MenuBuilderInterface;
use App\Service\UserExtensionService;
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

        if ($this->security->isGranted(WPTOrganizationVoter::WPT_ACCESS_SECTION, $organization)) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('workplace_training')
                ->setRouteName('workplace_training')
                ->setCaption('menu.workplace_training')
                ->setDescription('menu.workplace_training.detail')
                ->setIcon('store-alt')
                ->setPriority(5000);

            $root[] = $menu1;

            if ($this->security->isGranted(WPTOrganizationVoter::WPT_MANAGER, $organization)) {
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

            if ($this->security->isGranted(WPTOrganizationVoter::WPT_ACCESS, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('workplace_training_tracking')
                    ->setRouteName('workplace_training_tracking_list')
                    ->setCaption('menu.workplace_training.tracking')
                    ->setDescription('menu.workplace_training.tracking.detail')
                    ->setIcon('user-clock')
                    ->setPriority(2000);

                $menu1->addChild($menu2);
            }

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
            }

            if ($this->security->isGranted(WPTOrganizationVoter::WPT_ACCESS_VISIT, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('workplace_training_visit')
                    ->setRouteName('workplace_training_visit_teacher_list')
                    ->setCaption('menu.workplace_training.visit')
                    ->setDescription('menu.workplace_training.visit.detail')
                    ->setIcon('car-side')
                    ->setPriority(4000);

                $menu1->addChild($menu2);
            }

            if ($this->security->isGranted(WPTOrganizationVoter::WPT_ACCESS_EXPENSE, $organization)) {
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

        $menu2 = new MenuItem();
        $menu2
            ->setName('workplace_training_survey')
            ->setRouteName('workplace_training_survey')
            ->setCaption('menu.workplace_training.survey')
            ->setDescription('menu.workplace_training.survey.detail')
            ->setIcon('chart-pie')
            ->setPriority(6000);

        $menu1->addChild($menu2);

        if ($this->security->isGranted(WPTOrganizationVoter::WPT_MANAGER, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_DEPARTMENT_HEAD, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_EDUCATIONAL_TUTOR, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_GROUP_TUTOR, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_STUDENT, $organization)
        ) {
            $menu3 = new MenuItem();
            $menu3
                ->setName('workplace_training_survey_student')
                ->setRouteName('workplace_training_survey_student_list')
                ->setCaption('menu.workplace_training.survey.student')
                ->setDescription('menu.workplace_training.survey.student.detail')
                ->setIcon('child')
                ->setPriority(1000);

            $menu2->addChild($menu3);
        }

        if ($this->security->isGranted(WPTOrganizationVoter::WPT_MANAGER, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_DEPARTMENT_HEAD, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_GROUP_TUTOR, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_WORK_TUTOR, $organization)
        ) {
            $menu3 = new MenuItem();
            $menu3
                ->setName('workplace_training_survey_work_tutor')
                ->setRouteName('workplace_training_survey_work_tutor_list')
                ->setCaption('menu.workplace_training.survey.company')
                ->setDescription('menu.workplace_training.survey.company.detail')
                ->setIcon('industry')
                ->setPriority(2000);

            $menu2->addChild($menu3);
        }

        if ($this->security->isGranted(WPTOrganizationVoter::WPT_MANAGER, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_DEPARTMENT_HEAD, $organization) ||
            $this->security->isGranted(WPTOrganizationVoter::WPT_EDUCATIONAL_TUTOR, $organization)
        ) {
            $menu3 = new MenuItem();
            $menu3
                ->setName('workplace_training_survey_educational_tutor')
                ->setRouteName('workplace_training_survey_educational_tutor_list')
                ->setCaption('menu.workplace_training.survey.educational_tutor')
                ->setDescription('menu.workplace_training.survey.educational_tutor.detail')
                ->setIcon('user-clock')
                ->setPriority(5000);

            $menu2->addChild($menu3);
        }

        if ($this->security->isGranted(WPTOrganizationVoter::WPT_MANAGER, $organization)
        ) {
            $menu2 = new MenuItem();
            $menu2
                ->setName('workplace_training_report')
                ->setRouteName('workplace_training_report')
                ->setCaption('menu.workplace_training.report')
                ->setDescription('menu.workplace_training.report.detail')
                ->setIcon('file-alt')
                ->setPriority(7000);

            $menu1->addChild($menu2);

            $menu3 = new MenuItem();
            $menu3
                ->setName('workplace_training_report_student_survey')
                ->setRouteName('workplace_training_report_student_survey_list')
                ->setCaption('menu.workplace_training.report.student_survey')
                ->setDescription('menu.workplace_training.report.student_survey.detail')
                ->setIcon('chart-pie')
                ->setPriority(1000);

            $menu2->addChild($menu3);

            $menu3 = new MenuItem();
            $menu3
                ->setName('workplace_training_report_work_tutor_survey')
                ->setRouteName('workplace_training_report_work_tutor_survey_list')
                ->setCaption('menu.workplace_training.report.company_survey')
                ->setDescription('menu.workplace_training.report.company_survey.detail')
                ->setIcon('chart-pie')
                ->setPriority(2000);

            $menu2->addChild($menu3);

            $menu3 = new MenuItem();
            $menu3
                ->setName('workplace_training_report_educational_tutor_survey')
                ->setRouteName('workplace_training_report_educational_tutor_survey_list')
                ->setCaption('menu.workplace_training.report.educational_tutor_survey')
                ->setDescription('menu.workplace_training.report.educational_tutor_survey.detail')
                ->setIcon('chart-pie')
                ->setPriority(3000);

            $menu2->addChild($menu3);
        }

        return $root;
    }
}
