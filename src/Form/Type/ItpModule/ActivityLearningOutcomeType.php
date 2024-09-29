<?php

namespace App\Form\Type\ItpModule;

use App\Entity\ItpModule\ActivityLearningOutcome;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityLearningOutcomeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();
            $form
                ->add('shared', CheckboxType::class, [
                    'label' => 'form.shared',
                    'required' => false
                ])
                ->add('criteria', null, [
                    'multiple' => true,
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
            'data_class' => ActivityLearningOutcome::class,
            'translation_domain' => 'itp_activity'
        ]);
    }
}
