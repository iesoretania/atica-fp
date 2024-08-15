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

namespace App\Form\Type;

use App\Entity\Survey;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurveyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'form.title',
                'required' => true
            ]);

        if ($options['surveys']) {
            $builder
                ->add('copyFrom', EntityType::class, [
                    'label' => 'form.copy_from',
                    'class' => Survey::class,
                    'mapped' => false,
                    'choices' => $options['surveys'],
                    'choice_label' => fn(Survey $s): ?string => $s->getTitle(),
                    'placeholder' => 'form.copy_from.none',
                    'required' => false
                ]);
        }

        $builder
            ->add('startTimestamp', DateTimeType::class, [
                'label' => 'form.start_timestamp',
                'model_timezone' => 'UTC',
                'widget' => 'single_text',
                'required' => false
            ])
            ->add('endTimestamp', DateTimeType::class, [
                'label' => 'form.end_timestamp',
                'model_timezone' => 'UTC',
                'widget' => 'single_text',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'surveys' => [],
            'translation_domain' => 'survey'
        ]);
    }
}
