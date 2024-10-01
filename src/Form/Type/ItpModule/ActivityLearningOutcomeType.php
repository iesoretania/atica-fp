<?php

namespace App\Form\Type\ItpModule;

use App\Entity\Edu\Criterion;
use App\Entity\ItpModule\ProgramGradeLearningOutcome;
use App\Repository\Edu\CriterionRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityLearningOutcomeType extends AbstractType
{
    public function __construct(private readonly CriterionRepository $criterionRepository)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();
            $form
                ->add('shared', ChoiceType::class, [
                    'label' => 'form.shared',
                    'choices' => [
                        'form.shared.yes' => true,
                        'form.shared.no' => false
                    ],
                    'expanded' => true,
                    'placeholder' => 'form.shared.none',
                    'label_attr' => ['class' => 'radio-inline'],
                    'required' => false
                ])
                ->add('criteria', EntityType::class, [
                    'label' => 'form.criteria',
                    'class' => Criterion::class,
                    'choice_translation_domain' => false,
                    'choices' => $this->criterionRepository->findByLearningOutcome($data->getLearningOutcome()),
                    'multiple' => true,
                    'expanded' => true,
                    'required' => false
                ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProgramGradeLearningOutcome::class,
            'translation_domain' => 'itp_activity'
        ]);
    }
}
