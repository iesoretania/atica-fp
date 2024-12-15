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

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Subject;
use App\Entity\ItpModule\Activity;
use App\Repository\Edu\CriterionRepository;
use App\Repository\Edu\LearningOutcomeRepository;
use App\Repository\Edu\SubjectRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

class ActivityType extends AbstractType
{
    public function __construct(
        private readonly CriterionRepository       $criterionRepository,
        private readonly SubjectRepository         $subjectRepository,
        private readonly LearningOutcomeRepository $learningOutcomeRepository
    )
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $subjects = $this->subjectRepository->findByGrade($options['program_grade']->getGrade());
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
            ])
            ->add('subjects', EntityType::class, [
                'mapped' => false,
                'label' => 'form.subjects',
                'class' => Subject::class,
                'choices' => $subjects,
                'choice_label' => 'name',
                'choice_translation_domain' => false,
                'expanded' => true,
                'multiple' => true,
                'required' => true
            ]);

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options): void {
            $form = $event->getForm();
            $data = $event->getData();
            $subjects = isset($data['subjects'])
                ? $this->subjectRepository->findAllInListByIdAndAcademicYear(
                    $data['subjects'],
                    $options['program_grade']->getGrade()->getTraining()->getAcademicYear()
                )
                : [];
            $learningOutcomes = isset($data['learningOutcomes'])
                ? $this->learningOutcomeRepository->findAllInListByIdAndSubjects($data['learningOutcomes'], $subjects)
                : [];
            $this->addFormFields($form, $subjects, $learningOutcomes);
        });

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();
            assert($data instanceof Activity);
            $subjects = [];
            foreach ($data->getCriteria() as $criterion) {
                assert($criterion->getLearningOutcome() instanceof LearningOutcome);
                if (!in_array($criterion->getLearningOutcome()->getSubject(), $subjects, true)) {
                    $subjects[] = $criterion->getLearningOutcome()->getSubject();
                }
            }
            $learningOutcomes = [];
            foreach ($data->getCriteria() as $criterion) {
                assert($criterion instanceof Criterion);
                if (!in_array($criterion->getLearningOutcome(), $learningOutcomes, true)) {
                    $learningOutcomes[] = $criterion->getLearningOutcome();
                }
            }
            $this->addFormFields($form, $subjects, $learningOutcomes);
        });
    }

    private function addFormFields(FormInterface $form, array $subjects, array $learningOutcomes): void
    {
        $allLearningOutcomes = $subjects ? $this->learningOutcomeRepository->findBySubjects($subjects) : [];
        $criteria = $this->criterionRepository->findByLearningOutcomes($learningOutcomes);
        $form
            ->add('learningOutcomes', EntityType::class, [
                'mapped' => false,
                'label' => 'form.learning_outcomes',
                'class' => LearningOutcome::class,
                'choices' => $allLearningOutcomes,
                'data' => $learningOutcomes,
                'choice_translation_domain' => false,
                'choice_label' => function (LearningOutcome $learningOutcome) {
                    return $learningOutcome->getCode() . ' - ' . $learningOutcome->getDescription();
                },
                'multiple' => true,
                'expanded' => true,
                'required' => true
            ])
            ->add('criteria', EntityType::class, [
                'label' => 'form.criteria',
                'class' => Criterion::class,
                'choices' => $criteria,
                'choice_attr' => function ($e) {
                    return ['class' => 'lo_' . $e->getLearningOutcome()->getId()];
                },
                'constraints' => [
                    new Count(['min' => 1, 'minMessage' => 'selection.count.invalid.min'])
                ],
                'multiple' => true,
                'expanded' => true,
                'required' => true
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Activity::class,
            'constraints' => [
                new UniqueEntity(['fields' => ['code', 'programGrade']])
            ],
            'program_grade' => null,
            'translation_domain' => 'itp_activity'
        ]);
    }
}
