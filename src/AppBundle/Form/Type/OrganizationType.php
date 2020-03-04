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

use AppBundle\Entity\Organization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizationType extends AbstractType
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
            ->add('code', null, [
                'label' => 'form.code'
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
            ->add('webSite', null, [
                'label' => 'form.web_site'
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Organization::class,
            'translation_domain' => 'organization',
            'organization' => null,
            'new' => false
        ]);
    }
}
