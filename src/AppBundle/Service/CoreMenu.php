<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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
                ->setRouteName('work_linked_training')
                ->setCaption('menu.work_linked_training')
                ->setDescription('menu.work_linked_training.detail')
                ->setIcon('briefcase')
                ->setPriority(4000);

            $root[] = $menu1;

            $menu2 = new MenuItem();
            $menu2
                ->setName('work_linked_tracking_tracking')
                ->setRouteName('work_linked_training_tracking_list')
                ->setCaption('menu.work_linked_training.tracking')
                ->setDescription('menu.work_linked_training.tracking.detail')
                ->setIcon('user-clock');

            $menu1->addChild($menu2);

            if ($this->security->isGranted(OrganizationVoter::MANAGE_WORK_LINKED_TRAINING, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_agreement')
                    ->setRouteName('work_linked_training_agreement_list')
                    ->setCaption('menu.work_linked_training.agreement')
                    ->setDescription('menu.work_linked_training.agreement.detail')
                    ->setIcon('handshake');

                $menu1->addChild($menu2);

                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_learning_program')
                    ->setRouteName('work_linked_training_learning_program_list')
                    ->setCaption('menu.work_linked_training.learning_program')
                    ->setDescription('menu.work_linked_training.learning_program.detail')
                    ->setIcon('book');

                $menu1->addChild($menu2);
            }

            if ($this->security->isGranted(OrganizationVoter::ACCESS_TRAININGS, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_training')
                    ->setRouteName('work_linked_training_training')
                    ->setCaption('menu.work_linked_training.training')
                    ->setDescription('menu.work_linked_training.training.detail')
                    ->setIcon('graduation-cap')
                    ->setPriority(5000);

                $menu1->addChild($menu2);
            }

            if ($this->security->isGranted(OrganizationVoter::VIEW_EVALUATION_WORK_LINKED_TRAINING, $organization)) {
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

            if ($this->security->isGranted(OrganizationVoter::VIEW_GRADE_WORK_LINKED_TRAINING, $organization)) {
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

            if ($this->security->isGranted(OrganizationVoter::WLT_TEACHER, $organization)) {
                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_meeting')
                    ->setRouteName('work_linked_training_meeting_list')
                    ->setCaption('menu.work_linked_training.meeting')
                    ->setDescription('menu.work_linked_training.meeting.detail')
                    ->setIcon('user-friends')
                    ->setPriority(9000);

                $menu1->addChild($menu2);

                $menu2 = new MenuItem();
                $menu2
                    ->setName('work_linked_training_visit')
                    ->setRouteName('work_linked_training_visit_list')
                    ->setCaption('menu.work_linked_training.visit')
                    ->setDescription('menu.work_linked_training.visit.detail')
                    ->setIcon('car-side')
                    ->setPriority(8000);

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

            if ($this->security->isGranted(OrganizationVoter::WLT_MANAGER, $organization) ||
                $this->security->isGranted(OrganizationVoter::WLT_STUDENT, $organization)
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

            if ($this->security->isGranted(OrganizationVoter::WLT_MANAGER, $organization) ||
                $this->security->isGranted(OrganizationVoter::WLT_WORK_TUTOR, $organization)
            ) {
                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_survey_company')
                    ->setRouteName('work_linked_training_survey_company_list')
                    ->setCaption('menu.work_linked_training.survey.company')
                    ->setDescription('menu.work_linked_training.survey.company.detail')
                    ->setIcon('industry')
                    ->setPriority(2000);

                $menu2->addChild($menu3);
            }

            if ($this->security->isGranted(OrganizationVoter::WLT_MANAGER, $organization) ||
                $this->security->isGranted(OrganizationVoter::WLT_EDUCATIONAL_TUTOR, $organization)
            ) {
                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_survey_organization')
                    ->setRouteName('work_linked_training_survey_organization_list')
                    ->setCaption('menu.work_linked_training.survey.organization')
                    ->setDescription('menu.work_linked_training.survey.organization.detail')
                    ->setIcon('school')
                    ->setPriority(5000);

                $menu2->addChild($menu3);
            }

            if ($this->security->isGranted(OrganizationVoter::WLT_MANAGER, $organization)
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
                    ->setRouteName('work_linked_training_report_student_survey_report')
                    ->setCaption('menu.work_linked_training.report.student_survey')
                    ->setDescription('menu.work_linked_training.report.student_survey.detail')
                    ->setIcon('chart-pie')
                    ->setPriority(1000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_company_survey')
                    ->setRouteName('work_linked_training_report_company_survey_report')
                    ->setCaption('menu.work_linked_training.report.company_survey')
                    ->setDescription('menu.work_linked_training.report.company_survey.detail')
                    ->setIcon('chart-pie')
                    ->setPriority(2000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_organization_survey')
                    ->setRouteName('work_linked_training_report_organization_survey_report')
                    ->setCaption('menu.work_linked_training.report.organization_survey')
                    ->setDescription('menu.work_linked_training.report.organization_survey.detail')
                    ->setIcon('chart-pie')
                    ->setPriority(3000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_meeting')
                    ->setRouteName('work_linked_training_report_meeting_report')
                    ->setCaption('menu.work_linked_training.report.meeting')
                    ->setDescription('menu.work_linked_training.report.meeting.detail')
                    ->setIcon('user-friends')
                    ->setPriority(4000);

                $menu2->addChild($menu3);

                $menu3 = new MenuItem();
                $menu3
                    ->setName('work_linked_training_report_grading')
                    ->setRouteName('work_linked_training_report_grading_report')
                    ->setCaption('menu.work_linked_training.report.grading')
                    ->setDescription('menu.work_linked_training.report.grading.detail')
                    ->setIcon('chart-bar')
                    ->setPriority(5000);

                $menu2->addChild($menu3);
            }
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
                ->setName('organization_role')
                ->setRouteName('organization_role')
                ->setCaption('menu.organization.role')
                ->setDescription('menu.organization.role.detail')
                ->setIcon('user-tie')
                ->setPriority(11000);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_survey')
                ->setRouteName('organization_survey_list')
                ->setCaption('menu.organization.survey')
                ->setDescription('menu.organization.survey.detail')
                ->setIcon('chart-pie')
                ->setPriority(12000);

            $menu1->addChild($menu2);

            $menu2 = new MenuItem();
            $menu2
                ->setName('organization_import')
                ->setRouteName('organization_import')
                ->setCaption('menu.organization.import')
                ->setDescription('menu.organization.import.detail')
                ->setIcon('download')
                ->setPriority(13000);

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

        return $root;
    }
}
