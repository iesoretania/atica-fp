<?php

declare(strict_types=1);

namespace App\Form\Type\Edu;

use App\Entity\Edu\StudentEnrollment;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Selección de alumnado matriculado: casillas en tarjetas, separadas por grupo y a dos columnas (una en pantallas
 * pequeñas), con un botón «Seleccionar todos» por grupo (bloque «student_enrollment_choice_widget» del tema
 * ui/form_theme.html.twig). Reutilizable en cualquier formulario que deba mostrar alumnado: se usa como EntityType
 * (opciones «choices» o «query_builder»), con selección múltiple y expandida por defecto.
 *
 * Si se pide expanded=false o multiple=false se dibuja como un desplegable normal (agrupado por grupo).
 */
final class StudentEnrollmentChoiceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => StudentEnrollment::class,
            'multiple' => true,
            'expanded' => true,
            'choice_translation_domain' => false,
            // el grupo ya aparece como título del bloque: en cada tarjeta solo va el nombre de la persona
            'choice_label' => static fn (StudentEnrollment $enrollment): string => (string) $enrollment->getPerson(),
            'group_by' => static fn (StudentEnrollment $enrollment): ?string => (string) $enrollment->getGroup() ?: null,
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'student_enrollment_choice';
    }
}
