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

namespace App\Form\Type\WLT;

use App\Entity\Edu\Group;
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WLT\Project;
use App\Repository\Edu\GroupRepository;
use App\Repository\Edu\ReportTemplateRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\SurveyRepository;
use App\Service\UserExtensionService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjectType extends AbstractType
{
    private $teacherRepository;
    private $groupRepository;
    private $userExtensionService;
    private $surveyRepository;
    private $reportTemplateRepository;

    public function __construct(
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository,
        SurveyRepository $surveyRepository,
        ReportTemplateRepository $reportTemplateRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->teacherRepository = $teacherRepository;
        $this->groupRepository = $groupRepository;
        $this->userExtensionService = $userExtensionService;
        $this->surveyRepository = $surveyRepository;
        $this->reportTemplateRepository = $reportTemplateRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $organization = $this->userExtensionService->getCurrentOrganization();

        $teachers = $this->teacherRepository->findByOrganization($organization);
        $teacherPersons = array_map(static function(Teacher $teacher) {
            return $teacher->getPerson();
        }, $teachers);

        $groups = $this->groupRepository->findByOrganization($organization);

        $surveys = $this->surveyRepository->findByOrganization($organization);

        $templates = $this->reportTemplateRepository->findByOrganization($organization);

        $builder
            ->add('name', null, [
                'label' => 'form.name',
                'required' => true
            ])
            ->add('manager', EntityType::class, [
                'label' => 'form.manager',
                'class' => Person::class,
                'choice_translation_domain' => false,
                'choices' => $teacherPersons,
                'disabled' => $options['lock_manager'],
                'multiple' => false,
                'placeholder' => 'form.no_manager',
                'required' => false
            ])
            ->add('groups', EntityType::class, [
                'label' => 'form.groups',
                'class' => Group::class,
                'choice_translation_domain' => false,
                'choices' => $groups,
                'choice_label' => function (Group $group) {
                    return $group
                            ->getGrade()
                            ->getTraining()
                            ->getAcademicYear()
                            ->getDescription() . ' - ' . $group->getName();
                },
                'multiple' => true,
                'required' => true
            ])
            ->add('locked', ChoiceType::class, [
                'label' => 'form.locked',
                'expanded' => true,
                'choices' => [
                    'form.locked.no' => false,
                    'form.locked.yes' => true
                ]
            ])
            ->add('attendanceReportTemplate', EntityType::class, [
                'label' => 'form.attendance_report_template',
                'class' => ReportTemplate::class,
                'choice_label' => 'description',
                'choice_translation_domain' => false,
                'choices' => $templates,
                'placeholder' => 'form.no_template',
                'required' => false
            ])
            ->add('weeklyActivityReportTemplate', EntityType::class, [
                'label' => 'form.weekly_activity_report_template',
                'class' => ReportTemplate::class,
                'choice_label' => 'description',
                'choice_translation_domain' => false,
                'choices' => $templates,
                'placeholder' => 'form.no_template',
                'required' => false
            ])
            ->add('studentSurvey', EntityType::class, [
                'label' => 'form.student_survey',
                'class' => Survey::class,
                'choice_label' => 'title',
                'choice_translation_domain' => false,
                'choices' => $surveys,
                'placeholder' => 'form.no_survey',
                'required' => false
            ])
            ->add('companySurvey', EntityType::class, [
                'label' => 'form.company_survey',
                'class' => Survey::class,
                'choice_label' => 'title',
                'choice_translation_domain' => false,
                'choices' => $surveys,
                'placeholder' => 'form.no_survey',
                'required' => false
            ])
            ->add('educationalTutorSurvey', EntityType::class, [
                'label' => 'form.educational_tutor_survey',
                'class' => Survey::class,
                'choice_label' => 'title',
                'choice_translation_domain' => false,
                'choices' => $surveys,
                'placeholder' => 'form.no_survey',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
            'lock_manager' => false,
            'translation_domain' => 'wlt_project'
        ]);
    }
}
