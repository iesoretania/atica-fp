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

use AppBundle\Entity\WPT\Shift;
use AppBundle\Form\Model\WPT\ActivityCopy;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityCopyType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('shift', EntityType::class, [
                'label' => 'form.source_shift',
                'class' => Shift::class,
                'choices' => $options['shifts'],
                'choice_label' => static function (Shift $shift) {
                    return $shift->getSubject()->getGrade()
                            ->getTraining()->getAcademicYear() . ' - ' . $shift->getName();
                },
                'choice_translation_domain' => false,
                'placeholder' => 'form.source_shift.none',
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ActivityCopy::class,
            'shifts' => [],
            'translation_domain' => 'wpt_activity'
        ]);
    }
}
