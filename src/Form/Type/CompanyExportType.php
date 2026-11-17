<?php

namespace App\Form\Type;

use App\Entity\Edu\AcademicYear;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Opciones de la exportación de empresas a Excel (formato de nexo-fp): qué tutores laborales se incluyen como empleados.
 */
class CompanyExportType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('workplaceTraining', CheckboxType::class, [
                'label' => 'export.form.workplace_training',
                'required' => false,
            ])
            ->add('workLinkedLearning', CheckboxType::class, [
                'label' => 'export.form.work_linked_learning',
                'required' => false,
            ])
            ->add('inCompanyTraining', CheckboxType::class, [
                'label' => 'export.form.in_company_training',
                'required' => false,
            ])
            ->add('academicYear', EntityType::class, [
                'label' => 'export.form.academic_year',
                'class' => AcademicYear::class,
                'choices' => $options['academic_years'],
                'choice_translation_domain' => false,
                'placeholder' => 'export.form.academic_year.all',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'academic_years' => [],
            'method' => 'GET',
            'csrf_protection' => false,
            'translation_domain' => 'company',
        ]);
    }
}
