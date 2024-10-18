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

use App\Form\Model\ItpModule\CalendarAdd;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

class CalendarAddType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startDate', DateType::class, [
                'label' => 'form.start_date',
                'widget' => 'single_text',
                'required' => true
            ])
            ->add('totalHours', MoneyType::class, [
                'label' => 'form.total_hours',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new PositiveOrZero(),
                    new LessThanOrEqual(['value' => 100000, 'message' => 'calendar.week_hours.max'])
                ],
                'required' => true
            ])
            ->add('hoursMon', MoneyType::class, [
                'label' => 'form.hours_mon',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new PositiveOrZero(),
                    new LessThanOrEqual(['value' => 2400, 'message' => 'calendar.week_hours.max'])
                ],
                'required' => true
            ])
            ->add('hoursTue', MoneyType::class, [
                'label' => 'form.hours_tue',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new PositiveOrZero(),
                    new LessThanOrEqual(['value' => 2400, 'message' => 'calendar.week_hours.max'])
                ],
                'required' => true
            ])
            ->add('hoursWed', MoneyType::class, [
                'label' => 'form.hours_wed',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new PositiveOrZero(),
                    new LessThanOrEqual(['value' => 2400, 'message' => 'calendar.week_hours.max'])
                ],
                'required' => true
            ])
            ->add('hoursThu', MoneyType::class, [
                'label' => 'form.hours_thu',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new PositiveOrZero(),
                    new LessThanOrEqual(['value' => 2400, 'message' => 'calendar.week_hours.max'])
                ],
                'required' => true
            ])
            ->add('hoursFri', MoneyType::class, [
                'label' => 'form.hours_fri',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new PositiveOrZero(),
                    new LessThanOrEqual(['value' => 2400, 'message' => 'calendar.week_hours.max'])
                ],
                'required' => true
            ])
            ->add('hoursSat', MoneyType::class, [
                'label' => 'form.hours_sat',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new PositiveOrZero(),
                    new LessThanOrEqual(['value' => 2400, 'message' => 'calendar.week_hours.max'])
                ],
                'required' => true
            ])
            ->add('hoursSun', MoneyType::class, [
                'label' => 'form.hours_sun',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new PositiveOrZero(),
                    new LessThanOrEqual(['value' => 2400, 'message' => 'calendar.week_hours.max'])
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CalendarAdd::class,
            'translation_domain' => 'calendar'
        ]);
    }
}
