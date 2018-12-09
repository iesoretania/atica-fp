<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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
use AppBundle\Entity\Edu\Subject;
use AppBundle\Entity\WLT\Activity;
use AppBundle\Repository\Edu\CompetencyRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    /** @var CompetencyRepository $competencyRepository */
    private $competencyRepository;

    public function __construct(CompetencyRepository $competencyRepository)
    {
        $this->competencyRepository = $competencyRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var Subject $subject */
        $subject = $options['subject'];

        $competencies = $this->competencyRepository->findByTraining($subject->getGrade()->getTraining());

        $builder
            ->add('subject', EntityType::class, [
                'label' => 'form.subject',
                'class' => Subject::class,
                'choice_translation_domain' => false,
                'choices' => [$subject],
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
            ->add('competencies', EntityType::class, [
                'label' => 'form.competencies',
                'class' => Competency::class,
                'choice_translation_domain' => false,
                'choices' => $competencies,
                'multiple' => true,
                'required' => true
            ])
            ->add('priorLearning', null, [
                'label' => 'form.prior_learning',
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'subject' => null,
            'translation_domain' => 'wlt_activity'
        ]);
    }
}