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

use AppBundle\Entity\Company;
use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Entity\WLT\LearningProgram;
use AppBundle\Repository\WLT\ActivityRealizationRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LearningProgramType extends AbstractType
{
    private $activityRealizationRepository;

    public function __construct(ActivityRealizationRepository $activityRealizationRepository)
    {
        $this->activityRealizationRepository = $activityRealizationRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $activityRealizations =
            $this->activityRealizationRepository->findByProject($options['project']);

        $builder
            ->add('company', EntityType::class, [
                'label' => 'form.company',
                'class' => Company::class,
                'choice_label' => 'fullName',
                'choice_translation_domain' => false,
                'query_builder' => function (EntityRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->orderBy('c.name');
                },
                'placeholder' => 'form.company.none',
                'required' => true
            ])
            ->add('activityRealizations', EntityType::class, [
                'label' => 'form.activity_realizations',
                'class' => ActivityRealization::class,
                'expanded' => true,
                'group_by' => function (ActivityRealization $ar) {
                    return (string) $ar->getActivity();
                },
                'multiple' => true,
                'required' => false,
                'choices' => $activityRealizations
            ]);

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => LearningProgram::class,
            'project' => null,
            'translation_domain' => 'wlt_learning_program'
        ]);
    }
}
