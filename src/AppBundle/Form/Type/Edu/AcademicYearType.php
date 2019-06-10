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

namespace AppBundle\Form\Type\Edu;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Survey;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\SurveyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcademicYearType extends AbstractType
{
    private $teacherRepository;

    private $surveyRepository;

    public function __construct(TeacherRepository $teacherRepository, SurveyRepository $surveyRepository)
    {
        $this->teacherRepository = $teacherRepository;
        $this->surveyRepository = $surveyRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $teachers = $options['academic_year'] ? $this->teacherRepository->
            findByAcademicYear($options['academic_year']) : [];

        $surveys = $options['academic_year']
            ? $this->surveyRepository->findByOrganization($options['academic_year']->getOrganization())
            : [];

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
            ->add('wltOrganizationSurvey', EntityType::class, [
                'label' => 'form.wlt_organization_survey',
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
            'data_class' => AcademicYear::class,
            'translation_domain' => 'edu_academic_year',
            'academic_year' => null
        ]);
    }
}
