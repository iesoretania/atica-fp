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

use AppBundle\Entity\Edu\Criterion;
use AppBundle\Entity\WPT\Activity;
use AppBundle\Entity\WPT\Shift;
use AppBundle\Repository\Edu\CriterionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    private $criterionRepository;

    public function __construct(CriterionRepository $criterionRepository)
    {
        $this->criterionRepository = $criterionRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Shift $shift */
        $shift = $options['shift'];

        $criteria = $this->criterionRepository->findBySubject($shift->getSubject());

        $builder
            ->add('code', null, [
                'label' => 'form.code',
                'required' => true
            ])
            ->add('description', null, [
                'label' => 'form.description',
                'required' => true
            ])
            ->add('criteria', EntityType::class, [
                'label' => 'form.criteria',
                'class' => Criterion::class,
                'choice_translation_domain' => false,
                'choices' => $criteria,
                'group_by' => 'learningOutcome',
                'multiple' => true,
                'expanded' => true,
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'shift' => null,
            'translation_domain' => 'wpt_activity'
        ]);
    }
}
