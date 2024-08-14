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

namespace App\Form\Type\Edu;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Teacher;
use App\Repository\Edu\ReportTemplateRepository;
use App\Repository\Edu\TeacherRepository;
use App\Repository\SurveyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcademicYearType extends AbstractType
{
    public function __construct(private readonly TeacherRepository $teacherRepository, SurveyRepository $surveyRepository, private readonly ReportTemplateRepository $reportTemplateRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $templates = $options['academic_year'] ?
            $this->reportTemplateRepository->findByOrganization($options['academic_year']->getOrganization()) : [];

        $teachers = $options['academic_year'] ? $this->teacherRepository->
            findByAcademicYear($options['academic_year']) : [];

        $builder
            ->add('description', null, [
                'label' => 'form.description'
            ])
            ->add('startDate', null, [
                'label' => 'form.start_date',
                'widget' => 'single_text',
                'required' => true
            ])
            ->add('endDate', null, [
                'label' => 'form.end_date',
                'widget' => 'single_text',
                'required' => true
            ])
            ->add('principal', EntityType::class, [
                'label' => 'form.principal',
                'class' => Teacher::class,
                'choice_translation_domain' => false,
                'choices' => $teachers,
                'placeholder' => 'form.none',
                'required' => false
            ])
            ->add('financialManager', EntityType::class, [
                'label' => 'form.financial_manager',
                'class' => Teacher::class,
                'choice_translation_domain' => false,
                'choices' => $teachers,
                'placeholder' => 'form.none',
                'required' => false
            ])
            ->add('defaultPortraitTemplate', EntityType::class, [
                'label' => 'form.default_portrait_template',
                'class' => ReportTemplate::class,
                'choice_label' => 'description',
                'choice_translation_domain' => false,
                'choices' => $templates,
                'placeholder' => 'form.no_template',
                'required' => false
            ])
            ->add('defaultLandscapeTemplate', EntityType::class, [
                'label' => 'form.default_landscape_template',
                'class' => ReportTemplate::class,
                'choice_label' => 'description',
                'choice_translation_domain' => false,
                'choices' => $templates,
                'placeholder' => 'form.no_template',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AcademicYear::class,
            'translation_domain' => 'edu_academic_year',
            'academic_year' => null
        ]);
    }
}
