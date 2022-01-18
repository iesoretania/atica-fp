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

namespace App\Form\Type\WLT;

use App\Entity\WLT\Project;
use App\Form\Model\WLT\ActivityCopy;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityCopyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('project', EntityType::class, [
                'label' => 'form.source_project',
                'class' => Project::class,
                'choices' => $options['projects'],
                'choice_label' => 'name',
                'choice_translation_domain' => false,
                'placeholder' => 'form.source_project.none',
                'required' => true
            ])
            ->add('copyLearningProgram', ChoiceType::class, [
                'label' => 'form.copy_learning_program',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.copy_learning_program.yes' => true,
                    'form.copy_learning_program.no' => false
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ActivityCopy::class,
            'projects' => [],
            'translation_domain' => 'wlt_activity'
        ]);
    }
}
