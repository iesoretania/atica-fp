<?php

namespace App\Tests\Fixtures;

/**
 * Valores compartidos entre las fixtures y los tests, para que los tests no dependan
 * del orden de los datos ni de identificadores concretos.
 *
 * Los usuarios de cada organización se llaman "<prefijo>.<rol>" (por ejemplo, "alfa.jefe").
 * Ningún usuario de una organización pertenece a la otra.
 */
final class DemoData
{
    /** Contraseña de todos los usuarios de demostración */
    public const PASSWORD = 'demo1234';

    public const USER_ADMIN = 'admin';

    // Organización A (con datos de todos los módulos)
    public const ORG_A = 'alfa';
    public const ORG_A_CODE = '23000001';
    public const ORG_A_NAME = 'I.E.S. Alfa';

    // Organización B (sirve para probar los accesos cruzados entre centros)
    public const ORG_B = 'beta';
    public const ORG_B_CODE = '23000002';
    public const ORG_B_NAME = 'I.E.S. Beta';

    // Roles de cada organización (sufijo del nombre de usuario)
    public const ROLE_LOCAL_ADMIN = 'admin';
    public const ROLE_PRINCIPAL = 'director';
    public const ROLE_FINANCIAL_MANAGER = 'economico';
    public const ROLE_DEPARTMENT_HEAD = 'jefe';
    public const ROLE_GROUP_TUTOR = 'tutor';
    public const ROLE_TEACHER = 'profesor';
    public const ROLE_WORK_TUTOR = 'tutorlaboral';
    public const ROLE_WORK_TUTOR_2 = 'tutorlaboral2';
    public const ROLE_STUDENT_1 = 'alumno1';
    public const ROLE_STUDENT_2 = 'alumno2';
    public const ROLE_STUDENT_3 = 'alumno3';
    public const ROLE_STUDENT_4 = 'alumno4';

    // Atajos para la organización A
    public const USER_A_LOCAL_ADMIN = 'alfa.admin';
    public const USER_A_TEACHER = 'alfa.profesor';
    public const USER_B_LOCAL_ADMIN = 'beta.admin';
    public const USER_B_TEACHER = 'beta.profesor';

    /**
     * Datos de cada organización, por prefijo
     */
    public const ORGANIZATIONS = [
        self::ORG_A => ['code' => self::ORG_A_CODE, 'name' => self::ORG_A_NAME, 'city' => 'Linares'],
        self::ORG_B => ['code' => self::ORG_B_CODE, 'name' => self::ORG_B_NAME, 'city' => 'Úbeda'],
    ];

    /**
     * Período de prácticas de las fixtures: desde hace seis semanas hasta dentro de cuatro,
     * acotado al curso académico actual.
     *
     * @return array{\DateTimeImmutable, \DateTimeImmutable}
     */
    public static function trainingPeriod(?\DateTimeImmutable $today = null): array
    {
        $today ??= new \DateTimeImmutable('today');
        $yearStart = self::currentSchoolYearStart($today);
        $yearEnd = $yearStart->modify('+1 year -1 day');

        return [
            max($yearStart, $today->modify('-6 weeks')),
            min($yearEnd, $today->modify('+4 weeks')),
        ];
    }

    public static function username(string $prefix, string $role): string
    {
        return $prefix . '.' . $role;
    }

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
