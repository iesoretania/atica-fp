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

use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StudentProgramType extends AbstractType
{
    public function __construct(
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('modality', ChoiceType::class, [
                'label' => 'form.modality',
                'choices' => [
                    'form.modality.inherited' => ProgramGroup::MODE_INHERITED,
                    'form.modality.general' => ProgramGroup::MODE_GENERAL,
                    'form.modality.intensive' => ProgramGroup::MODE_INTENSIVE
                ],
                'expanded' => true,
                'required' => true
            ])
            ->add('authorizationNeeded', ChoiceType::class, [
                'label' => 'form.authorization_needed',
                'choices' => [
                    'form.authorization_needed.no' => false,
                    'form.authorization_needed.yes' => true
                ],
                'expanded' => true,
                'required' => true
            ])
            ->add('authorizationDescription', TextareaType::class, [
                'label' => 'form.authorization_description',
                'attr' => ['rows' => 3],
                'required' => false
            ])
            ->add('adaptationNeeded', ChoiceType::class, [
                'label' => 'form.adaptation_needed',
                'choices' => [
                    'form.adaptation_needed.no' => false,
                    'form.adaptation_needed.yes' => true
                ],
                'expanded' => true,
                'required' => true
            ])
            ->add('adaptationDescription', TextareaType::class, [
                'label' => 'form.adaptation_description',
                'attr' => ['rows' => 3],
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StudentProgram::class,
            'translation_domain' => 'itp_student_program'
        ]);
    }
}
