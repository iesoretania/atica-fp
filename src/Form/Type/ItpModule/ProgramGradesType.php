<?php

namespace App\Form\Type\ItpModule;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProgramGradesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('programGrades', CollectionType::class, [
            'entry_type' => ProgramGradeType::class,
            'required' => false,
            'label' => 'form.program_grades',
            'entry_options' => [
                'label' => false
            ]
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'itp_training_program'
        ]);
    }
}
