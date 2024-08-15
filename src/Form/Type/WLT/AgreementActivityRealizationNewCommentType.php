<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Form\Type\WLT;

use App\Entity\WLT\AgreementActivityRealization;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgreementActivityRealizationNewCommentType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('disabled', ChoiceType::class, [
                'label' => 'form.disabled',
                'required' => true,
                'expanded' => true,
                'choices' => [
                    'form.disabled.false' => false,
                    'form.disabled.true' => true
                ],
                'disabled' => !$options['can_be_disabled']
            ])
            ->add('newComment', TextareaType::class, [
                'label' => 'form.new_comment',
                'mapped' => false,
                'attr' => [
                    'rows' => 5
                ],
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgreementActivityRealization::class,
            'translation_domain' => 'wlt_agreement_activity_realization',
            'can_be_disabled' => true,
        ]);
    }
}
