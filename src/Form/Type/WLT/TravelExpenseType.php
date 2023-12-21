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

use App\Entity\Edu\TravelRoute;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\TravelExpense;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class TravelExpenseType extends AbstractType
{
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fromDateTime', DateTimeType::class, [
                'label' => 'form.from_datetime',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'model_timezone' => 'UTC',
                'required' => true
            ])
            ->add('toDateTime', DateTimeType::class, [
                'label' => 'form.to_datetime',
                'date_widget' => 'single_text',
                'time_widget' => 'single_text',
                'model_timezone' => 'UTC',
                'required' => true
            ])
            ->add('agreements', EntityType::class, [
                'label' => 'form.agreements',
                'class' => Agreement::class,
                'multiple' => true,
                'expanded' => true,
                'choices' => $options['agreements'],
                'required' => false
            ])
            ->add('travelRoute', Select2EntityType::class, [
                'label' => 'form.travel_route',
                'class' => TravelRoute::class,
                'minimum_input_length' => 2,
                'remote_route' => 'api_travel_route_query',
                'placeholder' => 'form.travel_route.placeholder',
                'attr' => ['class' => 'travel-route'],
                'required' => true
            ])
            ->add('otherExpenses', MoneyType::class, [
                'label' => 'form.other_expenses',
                'divisor' => 100,
                'required' => false
            ])
            ->add('otherExpensesDescription', TextareaType::class, [
                'label' => 'form.other_expenses_description',
                'attr' => [
                    'rows' => 10
                ],
                'required' => false
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'attr' => [
                    'rows' => 10
                ],
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => TravelExpense::class,
            'agreements' => [],
            'translation_domain' => 'wlt_travel_expense'
        ]);
    }
}
