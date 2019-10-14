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

namespace AppBundle\Form\Type\WLT;

use AppBundle\Entity\Edu\LearningOutcome;
use AppBundle\Entity\WLT\Activity;
use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Repository\Edu\LearningOutcomeRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityRealizationType extends AbstractType
{
    /** @var LearningOutcomeRepository */
    private $learningOutcomeRepository;

    public function __construct(LearningOutcomeRepository $learningOutcomeRepository)
    {
        $this->learningOutcomeRepository = $learningOutcomeRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Activity $activity */
        $activity = $options['activity'];

        $learningOutcomes = $this->learningOutcomeRepository
            ->findByGroups($activity->getProject()->getGroups());

        $builder
            ->add('activity', EntityType::class, [
                'label' => 'form.activity',
                'class' => Activity::class,
                'choice_translation_domain' => false,
                'choices' => [$activity],
                'choice_label' => 'code',
                'disabled' => true,
                'required' => true
            ])
            ->add('code', null, [
                'label' => 'form.code',
                'required' => true
            ])
            ->add('description', null, [
                'label' => 'form.description',
                'required' => true
            ])
            ->add('learningOutcomes', EntityType::class, [
                'label' => 'form.learning_outcomes',
                'class' => LearningOutcome::class,
                'choice_translation_domain' => false,
                'choices' => $learningOutcomes,
                'multiple' => true,
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ActivityRealization::class,
            'activity' => null,
            'translation_domain' => 'wlt_activity_realization'
        ]);
    }
}
