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
use App\Security\WLT\WLTOrganizationVoter;
use App\Service\MenuBuilderInterface;
use App\Service\UserExtensionService;
use Symfony\Component\Security\Core\Security;

class WLTMenu implements MenuBuilderInterface
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

        if ($this->security->isGranted(WLTOrganizationVoter::WLT_ACCESS, $organization)) {
            $menu1 = new MenuItem();
            $menu1
                ->setName('work_linked_training')
                ->setRouteName('work_linked_training')
                ->setCaption('menu.work_linked_training')
                ->setDescription('menu.work_linked_training.detail')
                ->setIcon('briefcase')
                ->setPriority(4000);

            $root[] = $menu1;

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_MANAGE, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_tracking_project')
                    ->setRouteName('work_linked_training_project_list')
                    ->setCaption('menu.work_linked_training.project')
                    ->setDescription('menu.work_linked_training.project.detail')
                    ->setIcon('folder-open');

                $menu1->addChild($menu2);
            }

            $menu2 = new MenuItem();
            $menu2
                ->setName('work_linked_tracking_tracking')
                ->setRouteName('work_linked_training_tracking_list')
                ->setCaption('menu.work_linked_training.tracking')
                ->setDescription('menu.work_linked_training.tracking.detail')
                ->setIcon('user-clock');

            $menu1->addChild($menu2);

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_VIEW_EVALUATION, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_evaluation')
                    ->setRouteName('work_linked_training_evaluation_list')
                    ->setCaption('menu.work_linked_training.evaluation')
                    ->setDescription('menu.work_linked_training.evaluation.detail')
                    ->setIcon('award')
                    ->setPriority(6000);

                $menu1->addChild($menu2);
            }

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_VIEW_GRADE, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_evaluation_summary')
                    ->setRouteName('work_linked_training_evaluation_summary_list')
                    ->setCaption('menu.work_linked_training.evaluation_summary')
                    ->setDescription('menu.work_linked_training.evaluation_summary.detail')
                    ->setIcon('chart-bar')
                    ->setPriority(6500);

                $menu1->addChild($menu2);
            }

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_ACCESS_VISIT, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_contact')
                    ->setRouteName('work_linked_training_contact_list')
                    ->setCaption('menu.work_linked_training.contact')
                    ->setDescription('menu.work_linked_training.contact.detail')
                    ->setIcon('broadcast-tower')
                    ->setPriority(8000);

                $menu1->addChild($menu2);
            }

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_ACCESS_EXPENSE, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_travel_expense')
                    ->setRouteName('work_linked_training_travel_expense_teacher_list')
                    ->setCaption('menu.work_linked_training.travel_expense')
                    ->setDescription('menu.work_linked_training.travel_expense.detail')
                    ->setIcon('road')
                    ->setPriority(8500);

                $menu1->addChild($menu2);
            }

            $menu2 = new MenuItem();
            $menu2
                ->setName('work_linked_training_survey')
                ->setRouteName('work_linked_training_survey')
                ->setCaption('menu.work_linked_training.survey')
                ->setDescription('menu.work_linked_training.survey.detail')
                ->setIcon('chart-pie')
                ->setPriority(10000);

            $menu1->addChild($menu2);

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization) ||
                $this->security->isGranted(WLTOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization) ||
                $this->security->isGranted(WLTOrganizationVoter::WLT_GROUP_TUTOR, $organization) ||
                $this->security->isGranted(WLTOrganizationVoter::WLT_STUDENT, $organization)
            ) {
                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_survey_student')
                    ->setRouteName('work_linked_training_survey_student_list')
                    ->setCaption('menu.work_linked_training.survey.student')
                    ->setDescription('menu.work_linked_training.survey.student.detail')
                    ->setIcon('child')
                    ->setPriority(1000);

                $menu2->addChild($menu3);
            }

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization) ||
                $this->security->isGranted(WLTOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization) ||
                $this->security->isGranted(WLTOrganizationVoter::WLT_GROUP_TUTOR, $organization) ||
                $this->security->isGranted(WLTOrganizationVoter::WLT_WORK_TUTOR, $organization)
            ) {
                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_survey_work_tutor')
                    ->setRouteName('work_linked_training_survey_work_tutor_list')
                    ->setCaption('menu.work_linked_training.survey.company')
                    ->setDescription('menu.work_linked_training.survey.company.detail')
                    ->setIcon('industry')
                    ->setPriority(2000);

                $menu2->addChild($menu3);
            }

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization) ||
                $this->security->isGranted(WLTOrganizationVoter::WLT_DEPARTMENT_HEAD, $organization) ||
                $this->security->isGranted(WLTOrganizationVoter::WLT_EDUCATIONAL_TUTOR, $organization)
            ) {
                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_survey_educational_tutor')
                    ->setRouteName('work_linked_training_survey_educational_tutor_list')
                    ->setCaption('menu.work_linked_training.survey.educational_tutor')
                    ->setDescription('menu.work_linked_training.survey.educational_tutor.detail')
                    ->setIcon('user-clock')
                    ->setPriority(5000);

                $menu2->addChild($menu3);
            }

            if ($this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization)
            ) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_report')
                    ->setRouteName('work_linked_training_report')
                    ->setCaption('menu.work_linked_training.report')
                    ->setDescription('menu.work_linked_training.report.detail')
                    ->setIcon('file-alt')
                    ->setPriority(11000);

                $menu1->addChild($menu2);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_student_survey')
                    ->setRouteName('work_linked_training_report_student_survey_list')
                    ->setCaption('menu.work_linked_training.report.student_survey')
                    ->setDescription('menu.work_linked_training.report.student_survey.detail')
                    ->setIcon('chart-pie')
                    ->setPriority(1000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_work_tutor_survey')
                    ->setRouteName('work_linked_training_report_work_tutor_survey_list')
                    ->setCaption('menu.work_linked_training.report.company_survey')
                    ->setDescription('menu.work_linked_training.report.company_survey.detail')
                    ->setIcon('chart-pie')
                    ->setPriority(2000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_educational_tutor_survey')
                    ->setRouteName('work_linked_training_report_educational_tutor_survey_list')
                    ->setCaption('menu.work_linked_training.report.educational_tutor_survey')
                    ->setDescription('menu.work_linked_training.report.educational_tutor_survey.detail')
                    ->setIcon('chart-pie')
                    ->setPriority(3000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_meeting')
                    ->setRouteName('work_linked_training_report_meeting_list')
                    ->setCaption('menu.work_linked_training.report.meeting')
                    ->setDescription('menu.work_linked_training.report.meeting.detail')
                    ->setIcon('user-friends')
                    ->setPriority(4000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_attendance')
                    ->setRouteName('work_linked_training_report_attendance_list')
                    ->setCaption('menu.work_linked_training.report.attendance')
                    ->setDescription('menu.work_linked_training.report.attendance.detail')
                    ->setIcon('user-check')
                    ->setPriority(5000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_grading')
                    ->setRouteName('work_linked_training_report_grading_list')
                    ->setCaption('menu.work_linked_training.report.grading')
                    ->setDescription('menu.work_linked_training.report.grading.detail')
                    ->setIcon('chart-bar')
                    ->setPriority(6000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_learning_program')
                    ->setRouteName('work_linked_training_report_learning_program_list')
                    ->setCaption('menu.work_linked_training.report.learning_program')
                    ->setDescription('menu.work_linked_training.report.learning_program.detail')
                    ->setIcon('chalkboard-teacher')
                    ->setPriority(7000);

                $menu2->addChild($menu3);
            }
        }

        return $root;
    }
}
