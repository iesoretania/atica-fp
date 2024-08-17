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

namespace App\Form\Type\WptModule;

use App\Entity\WltModule\Project;
use App\Form\Model\WptModule\ActivityCopyFromWlt;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityCopyFromWltType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('project', EntityType::class, [
                'label' => 'form.source_wlt_project',
                'class' => Project::class,
                'choices' => $options['projects'],
                'choice_label' => static fn(Project $project): ?string => $project->getName(),
                'choice_translation_domain' => false,
                'placeholder' => 'form.source_wlt_project.none',
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ActivityCopyFromWlt::class,
            'projects' => [],
            'translation_domain' => 'wpt_activity'
        ]);
    }
}
