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

namespace App\Form\Type\WPT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Subject;
use App\Entity\Survey;
use App\Entity\WPT\Shift;
use App\Repository\Edu\ReportTemplateRepository;
use App\Repository\Edu\SubjectRepository;
use App\Repository\SurveyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShiftType extends AbstractType
{
    public function __construct(private readonly SubjectRepository $subjectRepository, private readonly SurveyRepository $surveyRepository, private readonly ReportTemplateRepository $reportTemplateRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AcademicYear $academicYear */
        $academicYear = $options['academic_year'];
        $organization = $academicYear->getOrganization();

        $subjects = $this->subjectRepository->findByAcademicYear($academicYear);

        $surveys = $this->surveyRepository->findByOrganization($organization);

        $templates = $this->reportTemplateRepository->findByOrganization($organization);

        $builder
            ->add('name', null, [
                'label' => 'form.name',
                'required' => true
            ])
            ->add('subject', EntityType::class, [
                'label' => 'form.subject',
                'class' => Subject::class,
                'choice_translation_domain' => false,
                'choice_label' => fn(Subject $subject): string => $subject->getName() . ' - ' . $subject->getGrade() . ' - ' .
                    $subject->getGrade()->getTraining()->getAcademicYear()->getDescription(),
                'choices' => $subjects,
                'placeholder' => 'form.no_subject',
                'required' => true
            ])
            ->add('type', TextType::class, [
                'label' => 'form.type',
                'required' => true
            ])
            ->add('hours', IntegerType::class, [
                'label' => 'form.hours',
                'required' => true
            ])
            ->add('quarter', ChoiceType::class, [
                'label' => 'form.quarter',
                'choices' => [
                    'form.quarter.1Q' => Shift::QUARTER_FIRST,
                    'form.quarter.2Q' => Shift::QUARTER_SECOND,
                    'form.quarter.3Q' => Shift::QUARTER_THIRD
                ],
                'expanded' => true,
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
            'data_class' => Shift::class,
            'lock_manager' => false,
            'academic_year' => null,
            'translation_domain' => 'wpt_shift'
        ]);
    }
}
