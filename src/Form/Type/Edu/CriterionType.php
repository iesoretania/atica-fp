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

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Subject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CriterionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Subject $subject */
        $learningOutcome = $options['learning_outcome'];

        $builder
            ->add('learningOutcome', EntityType::class, [
                'label' => 'form.learning_outcome',
                'class' => LearningOutcome::class,
                'choice_translation_domain' => false,
                'choices' => [$learningOutcome],
                'disabled' => true,
                'required' => true
            ])
            ->add('code', TextType::class, [
                'label' => 'form.code',
                'required' => true
            ])
            ->add('name', TextareaType::class, [
                'label' => 'form.name',
                'required' => true,
                'attr' => [
                    'rows' => 10
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'required' => false,
                'attr' => [
                    'rows' => 10
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Criterion::class,
            'learning_outcome' => null,
            'translation_domain' => 'edu_criterion'
        ]);
    }
}
