<?php

namespace App\Tests\Fixtures;

/**
 * Valores compartidos entre las fixtures y los tests, para que los tests no dependan
 * del orden de los datos ni de identificadores concretos.
 */
final class DemoData
{
    /** Contraseña de todos los usuarios de demostración */
    public const PASSWORD = 'demo1234';

    // Organización A (con datos de todos los módulos)
    public const ORG_A_CODE = '23000001';
    public const ORG_A_NAME = 'I.E.S. Alfa';

    // Organización B (sirve para probar los accesos cruzados entre centros)
    public const ORG_B_CODE = '23000002';
    public const ORG_B_NAME = 'I.E.S. Beta';

    // Usuarios. Ningún usuario de una organización pertenece a la otra.
    public const USER_ADMIN = 'admin';
    public const USER_A_LOCAL_ADMIN = 'alfa.admin';
    public const USER_A_TEACHER = 'alfa.profesor';
    public const USER_B_LOCAL_ADMIN = 'beta.admin';
    public const USER_B_TEACHER = 'beta.profesor';

    /**
     * Fecha de inicio del curso académico actual (1 de septiembre) calculada a partir de hoy,
     * para que las comprobaciones que dependen de la fecha sigan siendo alcanzables con el tiempo.
     */
    public static function currentSchoolYearStart(?\DateTimeImmutable $today = null): \DateTimeImmutable
    {
        $today ??= new \DateTimeImmutable('today');
        $year = (int) $today->format('n') >= 9 ? (int) $today->format('Y') : (int) $today->format('Y') - 1;

        return new \DateTimeImmutable(sprintf('%d-09-01', $year));
    }

    private function __construct()
    {
    }
}
