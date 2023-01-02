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

namespace App\Form\Type\Edu;

use App\Entity\Edu\Teacher;
use App\Entity\Person;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tetranz\Select2EntityBundle\Form\Type\Select2EntityType;

class NewTeacherType extends AbstractType
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
                'label' => 'form.academic_year',
                'disabled' => true
            ])
            ->add('person', Select2EntityType::class, [
                'label' => 'form.person',
                'multiple' => false,
                'text_property' => 'fullDisplayName',
                'class' => Person::class,
                'minimum_input_length' => 3,
                'remote_route' => 'api_person_query',
                'placeholder' => 'form.teacher.none',
                'attr' => ['class' => 'person'],
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->buildBaseForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Teacher::class,
            'translation_domain' => 'edu_teacher',
            'disabled' => false
        ]);
    }
}
