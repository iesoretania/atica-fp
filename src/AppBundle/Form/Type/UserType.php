<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Form\Type;

use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    /**
     * Formulario base
     *
     * @param FormBuilderInterface $builder
     */
    private function buildBaseForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('loginUsername', null, [
                'label' => 'form.user_name',
                'disabled' => !$options['admin']
            ])
            ->add('personFirstName', null, [
                'label' => 'form.first_name',
                'property_path' => 'person.firstName',
                'disabled' => !$options['admin']
            ])
            ->add('personLastName', null, [
                'label' => 'form.last_name',
                'property_path' => 'person.lastName',
                'disabled' => !$options['admin']
            ])
            ->add('emailAddress', EmailType::class, [
                'label' => 'form.email_address',
                'required' => false,
            ])
            ->add('personGender', ChoiceType::class, [
                'label' => 'form.gender',
                'property_path' => 'person.gender',
                'expanded' => true,
                'choices' => [
                    'form.gender.neutral' => User::GENDER_NEUTRAL,
                    'form.gender.male' => User::GENDER_MALE,
                    'form.gender.female' => User::GENDER_FEMALE
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->buildBaseForm($builder, $options);

        if ($options['admin']) {
            $builder
                ->add('enabled', ChoiceType::class, [
                    'label' => 'form.enabled',
                    'disabled' => $options['own'],
                    'required' => true,
                    'expanded' => true,
                    'choices' => [
                        'form.enabled.yes' => true,
                        'form.enabled.no' => false
                    ]
                ])
                ->add('globalAdministrator', null, [
                    'label' => 'form.global_administrator',
                    'required' => false,
                    'disabled' => $options['own']
                ])
                ->add('allowExternalCheck', ChoiceType::class, [
                    'label' => 'form.allow_external_check',
                    'required' => true,
                    'expanded' => true,
                    'choices' => [
                        'form.allow_external_check.yes' => true,
                        'form.allow_external_check.no' => false
                    ]
                ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $builder = $event->getForm();
            /** @var User $data */
            $data = $event->getData();

            if ($data->getAllowExternalCheck() || $options['admin']) {
                $builder
                    ->add('externalCheck', ChoiceType::class, [
                        'label' => 'form.external_check',
                        'required' => true,
                        'expanded' => true,
                        'choices' => [
                            'form.external_check.yes' => true,
                            'form.external_check.no' => false
                        ]
                    ]);
            }

            $passwordChangeAllowed = !($data->getAllowExternalCheck() && $data->getExternalCheck());

            if (!$options['new']) {
                $builder
                    ->add('submit', SubmitType::class, [
                        'label' => 'form.save',
                        'attr' => ['class' => 'btn btn-success']
                    ]);

                if ($options['own'] && $passwordChangeAllowed) {
                    $builder
                        ->add('oldPassword', PasswordType::class, [
                            'label' => 'form.old_password',
                            'required' => false,
                            'mapped' => false,
                            'constraints' => [
                                new UserPassword([
                                    'groups' => ['password']
                                ]),
                                new NotBlank([
                                    'groups' => ['password']
                                ])
                            ]
                        ]);
                }
            }

            if ($options['admin'] || ($options['own'] && $passwordChangeAllowed)) {
                $builder
                    ->add('newPassword', RepeatedType::class, [
                        'label' => 'form.new_password',
                        'required' => $options['new'],
                        'type' => PasswordType::class,
                        'mapped' => false,
                        'invalid_message' => 'password.no_match',
                        'first_options' => [
                            'label' => 'form.new_password',
                            'constraints' => [
                                new Length([
                                    'min' => 7,
                                    'minMessage' => 'password.min_length',
                                    'groups' => ['password']
                                ]),
                                new NotBlank([
                                    'groups' => ['password']
                                ])
                            ]
                        ],
                        'second_options' => [
                            'label' => 'form.new_password_repeat',
                            'required' => $options['new']
                        ]
                    ])
                    ->add('changePassword', SubmitType::class, [
                        'label' => 'form.change_password',
                        'attr' => ['class' => 'btn btn-success'],
                        'validation_groups' => ['Default', 'password']
                    ]);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'translation_domain' => 'user',
            'admin' => false,
            'new' => false,
            'own' => false
        ]);
    }
}
