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

namespace App\Form\Type\Edu;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\ContactMethod;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactMethodType extends AbstractType
{
    public function __construct(
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('academicYear', EntityType::class, [
                'label' => 'form.academic_year',
                'class' => AcademicYear::class,
                'choice_translation_domain' => false,
                'choices' => [$options['academic_year']],
                'disabled' => true,
                'required' => true
            ])
            ->add('description', TextType::class, [
                'label' => 'form.description',
                'required' => true
            ])
            ->add('enabled', ChoiceType::class, [
                'label' => 'form.enabled',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.enabled.yes' => true,
                    'form.enabled.no' => false
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ContactMethod::class,
            'translation_domain' => 'edu_contact_method',
            'academic_year' => null
        ]);
    }
}
