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

use App\Form\Model\CalendarAdd;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

class CalendarAddType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startDate', DateType::class, [
                'label' => 'form.start_date',
                'widget' => 'single_text',
                'required' => true
            ])
            ->add('totalHours', null, [
                'label' => 'form.total_hours',
                'constraints' => [
                    new Range(['min' => 1, 'max' => 3000])
                ],
                'required' => true
            ])
            ->add('hoursMon', null, [
                'label' => 'form.hours_mon',
                'constraints' => [
                    new Range(['min' => 0, 'max' => 24])
                ],
                'required' => true
            ])
            ->add('hoursTue', null, [
                'label' => 'form.hours_tue',
                'constraints' => [
                    new Range(['min' => 0, 'max' => 24])
                ],
                'required' => true
            ])
            ->add('hoursWed', null, [
                'label' => 'form.hours_wed',
                'constraints' => [
                    new Range(['min' => 0, 'max' => 24])
                ],
                'required' => true
            ])
            ->add('hoursThu', null, [
                'label' => 'form.hours_thu',
                'constraints' => [
                    new Range(['min' => 0, 'max' => 24])
                ],
                'required' => true
            ])
            ->add('hoursFri', null, [
                'label' => 'form.hours_fri',
                'constraints' => [
                    new Range(['min' => 0, 'max' => 24])
                ],
                'required' => true
            ])
            ->add('hoursSat', null, [
                'label' => 'form.hours_sat',
                'constraints' => [
                    new Range(['min' => 0, 'max' => 24])
                ],
                'required' => true
            ])
            ->add('hoursSun', null, [
                'label' => 'form.hours_sun',
                'constraints' => [
                    new Range(['min' => 0, 'max' => 24])
                ],
                'required' => true
            ])
            ->add('overwriteAction', ChoiceType::class, [
                'label' => 'form.overwrite_action',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.overwrite_action.replace' => CalendarAdd::OVERWRITE_ACTION_REPLACE,
                    'form.overwrite_action.add' => CalendarAdd::OVERWRITE_ACTION_ADD
                ]
            ])
            ->add('ignoreNonWorkingDays', ChoiceType::class, [
                'label' => 'form.ignore_non_working_days',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.ignore_non_working_days.no' => false,
                    'form.ignore_non_working_days.yes' => true
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CalendarAdd::class,
            'translation_domain' => 'calendar'
        ]);
    }
}
