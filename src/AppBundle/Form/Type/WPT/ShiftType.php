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

namespace AppBundle\Form\Type\WPT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Grade;
use AppBundle\Entity\Edu\ReportTemplate;
use AppBundle\Entity\Survey;
use AppBundle\Entity\WPT\Shift;
use AppBundle\Repository\Edu\GradeRepository;
use AppBundle\Repository\Edu\ReportTemplateRepository;
use AppBundle\Repository\SurveyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShiftType extends AbstractType
{
    private $gradeRepository;
    private $surveyRepository;
    private $reportTemplateRepository;

    public function __construct(
        GradeRepository $gradeRepository,
        SurveyRepository $surveyRepository,
        ReportTemplateRepository $reportTemplateRepository
    ) {
        $this->gradeRepository = $gradeRepository;
        $this->surveyRepository = $surveyRepository;
        $this->reportTemplateRepository = $reportTemplateRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var AcademicYear $academicYear */
        $academicYear = $options['academic_year'];
        $organization = $academicYear->getOrganization();

        $grades = $this->gradeRepository->findByAcademicYear($academicYear);

        $surveys = $this->surveyRepository->findByOrganization($organization);

        $templates = $this->reportTemplateRepository->findByOrganization($organization);

        $builder
            ->add('name', null, [
                'label' => 'form.name',
                'required' => true
            ])
            ->add('grade', EntityType::class, [
                'label' => 'form.grade',
                'class' => Grade::class,
                'choice_translation_domain' => false,
                'choice_label' => function (Grade $grade) {
                    return $grade->getName() . ' - ' . $grade->getTraining()->getAcademicYear();
                },
                'choices' => $grades,
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
            'data_class' => Shift::class,
            'lock_manager' => false,
            'academic_year' => null,
            'translation_domain' => 'wpt_shift'
        ]);
    }
}
