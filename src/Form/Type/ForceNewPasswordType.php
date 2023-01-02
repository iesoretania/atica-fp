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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotNull;

class ForceNewPasswordType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', PasswordType::class, [
                'label' => 'form.reset.password_old',
                'constraints' => [
                    new UserPassword()
                ],
                'mapped' => false,
                'attr' => ['tabindex' => 2, 'autofocus' => ''],
                'required' => true
            ])
            ->add('newPassword', RepeatedType::class, [
                'required' => true,
                'type' => PasswordType::class,
                'mapped' => false,
                'invalid_message' => 'password.no_match',
                'first_options' => [
                    'label' => 'form.reset.password_new',
                    'constraints' => [
                        new Length([
                            'min' => 8,
                            'minMessage' => 'password.min_length'
                        ]),
                        new NotNull()
                    ],
                    'attr' => ['tabindex' => 3, 'autofocus' => ''],
                ],
                'second_options' => [
                    'label' => 'form.reset.password_repeat',
                    'attr' => ['tabindex' => 4]
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'translation_domain' => 'security'
        ]);
    }
}
