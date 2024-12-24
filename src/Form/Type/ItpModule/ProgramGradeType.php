<?php

namespace App\Form\Type\ItpModule;

use App\Entity\ItpModule\ProgramGrade;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;

class ProgramGradeType extends AbstractType
{
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('targetHours', MoneyType::class, [
                'label' => 'form.target_hours',
                'currency' => false,
                'divisor' => 100,
                'constraints' => [
                    new GreaterThanOrEqual(0)
                ],
                'required' => false
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProgramGrade::class,
            'translation_domain' => 'itp_grade'
        ]);
    }

}
