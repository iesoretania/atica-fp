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

namespace AppBundle\Form\Type\WPT;

use AppBundle\Entity\WPT\Agreement;
use AppBundle\Form\Model\WPT\CalendarCopy;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CalendarCopyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('agreement', EntityType::class, [
                'label' => 'form.source_agreement',
                'class' => Agreement::class,
                'choices' => $options['agreements'],
                'choice_translation_domain' => false,
                'placeholder' => 'form.source_agreement.none',
                'required' => true
            ])
            ->add('overwriteAction', ChoiceType::class, [
                'label' => 'form.overwrite_action',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.overwrite_action.replace' => CalendarCopy::OVERWRITE_ACTION_REPLACE,
                    'form.overwrite_action.add' => CalendarCopy::OVERWRITE_ACTION_ADD
                ]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CalendarCopy::class,
            'agreements' => [],
            'translation_domain' => 'calendar'
        ]);
    }
}
