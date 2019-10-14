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

use AppBundle\Entity\Edu\Competency;
use AppBundle\Entity\WLT\Activity;
use AppBundle\Entity\WLT\Project;
use AppBundle\Repository\WLT\WLTCompetencyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    private $WLTCompetencyRepository;

    public function __construct(WLTCompetencyRepository $WLTCompetencyRepository)
    {
        $this->WLTCompetencyRepository = $WLTCompetencyRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Project $project */
        $project = $options['project'];

        $competencies = $this->WLTCompetencyRepository->findByProject($project);

        $builder
            ->add('code', null, [
                'label' => 'form.code',
                'required' => true
            ])
            ->add('description', null, [
                'label' => 'form.description',
                'required' => true
            ])
            ->add('competencies', EntityType::class, [
                'label' => 'form.competencies',
                'class' => Competency::class,
                'choice_translation_domain' => false,
                'choices' => $competencies,
                'multiple' => true,
                'required' => false
            ])
            ->add('priorLearning', null, [
                'label' => 'form.prior_learning',
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
            'project' => null,
            'translation_domain' => 'wlt_activity'
        ]);
    }
}
