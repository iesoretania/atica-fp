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

namespace App\Form\Type;

use App\Entity\Person;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NewPersonType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('uniqueIdentifier', null, [
                'label' => 'form.unique_identifier',
                'attr' => [
                    'placeholder' => 'form.unique_identifier.placeholder'
                ],
                'required' => false
            ])
            ->add('loginUsername', null, [
                'label' => 'form.user_name',
                'required' => true
            ])
            ->add('firstName', null, [
                'label' => 'form.first_name',
                'required' => true
            ])
            ->add('lastName', null, [
                'label' => 'form.last_name',
                'required' => true
            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'form.gender',
                'expanded' => true,
                'choices' => [
                    'form.gender.neutral' => Person::GENDER_NEUTRAL,
                    'form.gender.male' => Person::GENDER_MALE,
                    'form.gender.female' => Person::GENDER_FEMALE
                ]
            ])
            ->add('emailAddress', EmailType::class, [
                'label' => 'form.email_address',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'constraints' => [
                new UniqueEntity(['fields' => 'uniqueIdentifier']),
                new UniqueEntity(['fields' => 'emailAddress'])
            ],
            'data_class' => Person::class,
            'translation_domain' => 'person'
        ]);
    }
}
