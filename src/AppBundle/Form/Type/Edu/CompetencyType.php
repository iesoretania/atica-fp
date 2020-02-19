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

namespace AppBundle\Form\Type\Edu;

use AppBundle\Entity\Edu\Competency;
use AppBundle\Entity\Edu\Training;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompetencyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Training $training */
        $training = $options['training'];

        $builder
            ->add('training', EntityType::class, [
                'label' => 'form.training',
                'class' => Training::class,
                'choice_translation_domain' => false,
                'choices' => [$training],
                'disabled' => true,
                'required' => true
            ])
            ->add('code', null, [
                'label' => 'form.code',
                'required' => true
            ])
            ->add('description', null, [
                'label' => 'form.description',
                'required' => true,
                'attr' => [
                    'rows' => 8
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Competency::class,
            'training' => null,
            'translation_domain' => 'edu_competency'
        ]);
    }
}
