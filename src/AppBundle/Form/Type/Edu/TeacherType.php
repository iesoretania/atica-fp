<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TeacherType extends AbstractType
{
    /**
     * Formulario base
     *
     * @param FormBuilderInterface $builder
     */
    private function buildBaseForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('academicYear', null, [
                'label' => 'form.academic_year'
            ])
            ->add('loginUsername', null, [
                'label' => 'form.user_name',
                'property_path' => 'person.user.loginUsername'
            ])
            ->add('personFirstName', null, [
                'label' => 'form.first_name',
                'property_path' => 'person.firstName'
            ])
            ->add('personLastName', null, [
                'label' => 'form.last_name',
                'property_path' => 'person.lastName'
            ])
            ->add('emailAddress', EmailType::class, [
                'label' => 'form.email_address',
                'property_path' => 'person.user.emailAddress'
            ])
            ->add('personGender', ChoiceType::class, [
                'label' => 'form.gender',
                'expanded' => true,
                'property_path' => 'person.gender',
                'choices' => [
                    'form.gender.neutral' => User::GENDER_NEUTRAL,
                    'form.gender.male' => User::GENDER_MALE,
                    'form.gender.female' => User::GENDER_FEMALE
                ]
            ])
            ->add('enabled', ChoiceType::class, [
                'label' => 'form.enabled',
                'disabled' => true,
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.enabled.yes' => true,
                    'form.enabled.no' => false
                ],
                'property_path' => 'person.user.enabled'
            ])
            ->add('allowExternalCheck', ChoiceType::class, [
                'label' => 'form.allow_external_check',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.allow_external_check.yes' => true,
                    'form.allow_external_check.no' => false
                ],
                'property_path' => 'person.user.allowExternalCheck',
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->buildBaseForm($builder, $options);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $builder = $event->getForm();
            /** @var User $data */
            $data = $event->getData();

            if ($data->getPerson()->getUser()->getAllowExternalCheck() || $options['admin']) {
                $builder
                    ->add('externalCheck', ChoiceType::class, [
                        'label' => 'form.external_check',
                        'required' => true,
                        'expanded' => true,
                        'choices' => [
                            'form.external_check.yes' => true,
                            'form.external_check.no' => false
                        ],
                        'property_path' => 'person.user.externalCheck',
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
            'data_class' => Teacher::class,
            'translation_domain' => 'edu_teacher',
            'disabled' => true
        ]);
    }
}
