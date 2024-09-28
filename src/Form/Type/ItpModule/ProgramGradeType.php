<?php

namespace App\Form\Type\ItpModule;

use App\Entity\ItpModule\ProgramGrade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProgramGradeType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData();
            $form->add('targetHours', MoneyType::class, [
                'label' => $data->getGrade(),
                'currency' => false,
                'divisor' => 100,
                'translation_domain' => false,
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
            'data_class' => ProgramGrade::class,
            'translation_domain' => 'itp_training_program'
        ]);
    }
}
