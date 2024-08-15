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

namespace App\Form\Type\WPT;

use App\Entity\WPT\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FinalReportType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $aspects = [
            'form.grade_negative' => Report::GRADE_NEGATIVE,
            'form.grade_positive' => Report::GRADE_POSITIVE,
            'form.grade_excellent' => Report::GRADE_EXCELLENT
        ];

        $builder
            ->add('workActivities', TextareaType::class, [
                'label' => 'form.work_activities',
                'attr' => [
                    'rows' => 10
                ],
                'required' => true
            ])
            ->add('professionalCompetence', ChoiceType::class, [
                'label' => 'form.professional_competence',
                'expanded' => true,
                'choices' => $aspects,
                'required' => true
            ])
            ->add('organizationalCompetence', ChoiceType::class, [
                'label' => 'form.organizational_competence',
                'expanded' => true,
                'choices' => $aspects,
                'required' => true
            ])
            ->add('relationalCompetence', ChoiceType::class, [
                'label' => 'form.relational_competence',
                'expanded' => true,
                'choices' => $aspects,
                'required' => true
            ])
            ->add('contingencyResponse', ChoiceType::class, [
                'label' => 'form.contingency_response',
                'expanded' => true,
                'choices' => $aspects,
                'required' => true
            ])
            ->add('proposedChanges', TextareaType::class, [
                'label' => 'form.proposed_changes',
                'attr' => [
                    'rows' => 10
                ],
                'required' => false
            ])
            ->add('signDate', DateType::class, [
                'label' => 'form.sign_date',
                'widget' => 'single_text',
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Report::class,
            'translation_domain' => 'wpt_final_report'
        ]);
    }
}
