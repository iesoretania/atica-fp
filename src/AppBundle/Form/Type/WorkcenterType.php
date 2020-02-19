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

namespace AppBundle\Form\Type;

use AppBundle\Entity\Person;
use AppBundle\Entity\Workcenter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class WorkcenterType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', null, [
                'label' => 'form.name'
            ])
            ->add('emailAddress', null, [
                'label' => 'form.email_address'
            ])
            ->add('address', null, [
                'label' => 'form.address'
            ])
            ->add('city', null, [
                'label' => 'form.city'
            ])
            ->add('zipCode', null, [
                'label' => 'form.zip_code'
            ])
            ->add('phoneNumber', null, [
                'label' => 'form.phone_number'
            ])
            ->add('faxNumber', null, [
                'label' => 'form.fax_number'
            ])
            ->add('manager', Select2EntityType::class, [
                'label' => 'form.manager',
                'multiple' => false,
                'text_property' => 'fullDisplayName',
                'class' => Person::class,
                'minimum_input_length' => 9,
                'remote_route' => 'api_person_query',
                'placeholder' => 'form.manager.no_manager',
                'attr' => ['class' => 'person'],
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Workcenter::class,
            'translation_domain' => 'workcenter'
        ]);
    }
}
