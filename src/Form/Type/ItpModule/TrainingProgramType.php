<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Form\Type\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Organization;
use App\Entity\Survey;
use App\Repository\Edu\PerformanceScaleRepository;
use App\Repository\Edu\ReportTemplateRepository;
use App\Repository\Edu\TrainingRepository;
use App\Repository\SurveyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class TrainingProgramType extends AbstractType
{
    public function __construct(
        private readonly TrainingRepository $trainingRepository,
        private readonly SurveyRepository $surveyRepository,
        private readonly ReportTemplateRepository $reportTemplateRepository,
        private readonly PerformanceScaleRepository $scaleRepository
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $academicYear = $options['academic_year'];
        assert($academicYear instanceof AcademicYear);
        $organization = $academicYear->getOrganization();

        $trainings = $this->trainingRepository->findByAcademicYear($academicYear);

        $scales = $this->scaleRepository->findByOrganization($organization);

        assert($organization instanceof Organization);
        $surveys = $this->surveyRepository->findByOrganization($organization);

        $templates = $this->reportTemplateRepository->findByOrganization($organization);

        $builder
            ->add('name', TextType::class, [
                'label' => 'form.name',
                'required' => true
            ])
            ->add('training', EntityType::class, [
                'label' => 'form.training',
                'class' => Training::class,
                'choice_translation_domain' => false,
                'choice_label' => 'name',
                'choices' => $trainings,
                'placeholder' => 'form.no_training',
                'disabled' => !$options['new'],
                'required' => true
            ])
            ->add('modality', ChoiceType::class, [
                'label' => 'form.modality',
                'choices' => [
                    'form.modality.general' => TrainingProgram::MODE_GENERAL,
                    'form.modality.intensive' => TrainingProgram::MODE_INTENSIVE
                ],
                'expanded' => true,
                'required' => true
            ])
            ->add('targetHours', MoneyType::class, [
                'label' => 'form.target_hours',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new GreaterThanOrEqual(0)
                ],
                'required' => false
            ])
            ->add('locked', ChoiceType::class, [
                'label' => 'form.locked',
                'expanded' => true,
                'choices' => [
                    'form.locked.no' => false,
                    'form.locked.yes' => true
                ]
            ])
            ->add('performanceScale', EntityType::class, [
                'label' => 'form.performance_scale',
                'class' => PerformanceScale::class,
                'choice_translation_domain' => false,
                'choice_label' => 'description',
                'choices' => $scales,
                'placeholder' => 'form.no_performance_scale',
                'required' => true
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
            ->add('weeklyActivityReportTemplateType', ChoiceType::class, [
                'label' => 'form.weekly_activity_report_template_type',
                'expanded' => true,
                'choices' => [
                    'form.weekly_activity_report_template_type.5_days_week' => TrainingProgram::WEEK_5_DAYS,
                    'form.weekly_activity_report_template_type.7_days_week' => TrainingProgram::WEEK_7_DAYS
                ],
                'required' => true
            ])
            ->add('activitySummaryReportTemplate', EntityType::class, [
                'label' => 'form.activity_summary_report_template',
                'class' => ReportTemplate::class,
                'choice_label' => 'description',
                'choice_translation_domain' => false,
                'choices' => $templates,
                'placeholder' => 'form.no_template',
                'required' => false
            ])
            ->add('finalReportTemplate', EntityType::class, [
                'label' => 'form.final_report_template',
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TrainingProgram::class,
            'lock_manager' => false,
            'academic_year' => null,
            'new' => false,
            'is_manager' => false,
            'departments' => [],
            'translation_domain' => 'itp_training_program'
        ]);
    }
}
