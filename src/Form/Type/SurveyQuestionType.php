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

namespace App\Form\Type;

use App\Entity\SurveyQuestion;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SurveyQuestionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('description', CKEditorType::class, [
                'label' => 'form.description',
                'required' => true,
                'attr' => ['rows' => 8]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'form.type',
                'disabled' => $options['locked'],
                'choices' => SurveyQuestion::TYPES,
                'choice_label' => function ($item) {
                    return 'type.' . $item;
                },
                'choice_translation_domain' => 'survey_question',
                'required' => true
            ])
            ->add('items', TextType::class, [
                'label' => 'form.items',
                'disabled' => $options['locked'],
                'required' => false,
                'attr' => ['placeholder' => 'form.items.placeholder']
            ])
            ->add('mandatory', ChoiceType::class, [
                'label' => 'form.mandatory',
                'disabled' => $options['locked'],
                'expanded' => true,
                'choices' => [
                    'form.mandatory.yes' => true,
                    'form.mandatory.no' => false
                ],
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyQuestion::class,
            'translation_domain' => 'survey_question',
            'locked' => false
        ]);
    }
}
