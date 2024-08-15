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

use App\Entity\Edu\Competency;
use App\Entity\WLT\Activity;
use App\Entity\WLT\Project;
use App\Repository\WLT\WLTCompetencyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function __construct(private readonly WLTCompetencyRepository $WLTCompetencyRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'project' => null,
            'translation_domain' => 'wlt_activity'
        ]);
    }
}
