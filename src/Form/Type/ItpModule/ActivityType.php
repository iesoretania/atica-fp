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

namespace App\Form\Type\ItpModule;

use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use App\Repository\Edu\SubjectRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class ActivityType extends AbstractType
{
    public function __construct(
        private readonly SubjectRepository $subjectRepository,
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code', TextType::class, [
                'label' => 'form.code',
                'required' => true
            ])
            ->add('name', TextType::class, [
                'label' => 'form.name',
                'required' => true
            ])
            ->add('description', TextareaType::class, [
                'label' => 'form.description',
                'required' => false
            ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            /** @var Activity $data */
            $data = $event->getData();
            assert($data instanceof Activity);
            assert($data->getProgramGrade() instanceof ProgramGrade);
            $subjects = $this->subjectRepository->findByGrade($data->getProgramGrade()->getGrade());

            $form
                ->add('subjects', ChoiceType::class, [
                    'mapped' => false,
                    'label' => 'form.subjects',
                    'choice_label' => 'name',
                    'choices' => $subjects,
                    'constraints' => [
                        new Count(['min' => 1])
                    ],
                    'multiple' => true,
                    'expanded' => true,
                    'required' => true
                ]);
            $form
                ->add('assignedLearningOutcomes', CollectionType::class, [
                    'label' => 'form.learning_outcomes',
                    'entry_type' => ActivityLearningOutcomeType::class,
                    'entry_options' => [
                        'label' => false
                    ]
                ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'activity_learning_outcomes' => [],
            'translation_domain' => 'itp_activity'
        ]);
    }
}
