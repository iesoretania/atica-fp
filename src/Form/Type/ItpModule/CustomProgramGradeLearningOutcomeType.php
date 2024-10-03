<?php

namespace App\Form\Type\ItpModule;

use App\Form\Model\ItpModule\CustomProgramGradeLearningOutcome;
use App\Repository\ItpModule\ProgramGradeLearningOutcomeRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CustomProgramGradeLearningOutcomeType extends AbstractType
{
    public function __construct(private readonly ProgramGradeLearningOutcomeRepository $programGradeLearningOutcomeRepository)
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
                //->add('programGradeLearningOutcome', HiddenType::class)
                ->add('selected', ChoiceType::class, [
                    'label' => $data->getProgramGradeLearningOutcome()->getLearningOutcome()->getCode() . ': ' . $data->getProgramGradeLearningOutcome()->getLearningOutcome()->getDescription(),
                    'choices' => [
                        'form.learning_outcome.not_selected' => 0,
                        'form.learning_outcome.shared' => 1,
                        'form.learning_outcome.exclusive' => 2,
                    ],
                    'choice_attr' => function ($e) use ($data) {
                        return ['class' => 'lo_' . $data->getProgramGradeLearningOutcome()->getLearningOutcome()->getSubject()->getId() . '_' . $e];
                    },
                    'label_attr' => [
                        'class' => 'radio-inline'
                    ],
                    'expanded' => true,
                    'translation_domain' => false,
                    'choice_translation_domain' => 'itp_grade',
                    'required' => true
                ]);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CustomProgramGradeLearningOutcome::class,
            'translation_domain' => 'itp_grade'
        ]);
    }
}
