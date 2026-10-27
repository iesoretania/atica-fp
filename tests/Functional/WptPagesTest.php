<?php

namespace App\Tests\Functional;

use App\Entity\Edu\StudentEnrollment;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Shift;
use App\Tests\Fixtures\DemoData;

/**
 * Módulo de Formación en Centros de Trabajo (FCT): qué ve cada rol y aislamiento entre organizaciones.
 * Las expectativas se derivan de los Voters de src/Security/WptModule.
 */
class WptPagesTest extends AppWebTestCase
{
    private const ADMINS = [DemoData::USER_ADMIN, 'alfa.admin'];

    /**
     * Roles de la organización A que pueden acceder a cada pantalla (además de los administradores)
     */
    private const ACCESS = [
        // inicio del módulo: cualquiera que intervenga en las prácticas o gestione gastos
        'home' => ['alfa.jefe', 'alfa.economico', 'alfa.tutor', 'alfa.profesor', 'alfa.alumno1', 'alfa.tutorlaboral'],
        // gestión de convocatorias y convenios: solo administración y jefatura de departamento
        'shifts' => ['alfa.jefe'],
        'agreements' => ['alfa.jefe'],
        'companies' => ['alfa.jefe'],
        // seguimiento: todos los que participan, menos economía
        'tracking' => ['alfa.jefe', 'alfa.tutor', 'alfa.profesor', 'alfa.alumno1', 'alfa.tutorlaboral'],
        // calendario de la matrícula de alumno1: su tutor docente, él mismo y su tutor laboral
        'calendar' => ['alfa.jefe', 'alfa.tutor', 'alfa.alumno1', 'alfa.tutorlaboral'],
        // visitas: tutores docentes y jefatura
        'visits' => ['alfa.jefe', 'alfa.tutor', 'alfa.profesor'],
        // gastos: además, el responsable económico
        'expenses' => ['alfa.jefe', 'alfa.economico', 'alfa.tutor', 'alfa.profesor'],
        'final-report' => ['alfa.jefe', 'alfa.tutor', 'alfa.profesor', 'alfa.tutorlaboral'],
    ];

    private const ALL_ROLES = [
        'alfa.director', 'alfa.economico', 'alfa.jefe', 'alfa.tutor', 'alfa.profesor',
        'alfa.alumno1', 'alfa.alumno4', 'alfa.tutorlaboral',
    ];

    /**
     * @return array{string, array<string, mixed>}
     */
    private function route(string $screen, string $organizationCode): array
    {
        $doctrine = static::getContainer()->get('doctrine');
        $academicYear = $this->getOrganization($organizationCode)->getCurrentAcademicYear()->getId();
        $shift = $doctrine->getRepository(Shift::class)->createQueryBuilder('s')
            ->join('s.subject', 'su')->join('su.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.id = :ay')->setParameter('ay', $academicYear)
            ->setMaxResults(1)->getQuery()->getSingleResult();

        return match ($screen) {
            'home' => ['workplace_training', []],
            'shifts' => ['workplace_training_shift_list', ['academicYear' => $academicYear]],
            'agreements' => ['workplace_training_agreement_list', ['id' => $shift->getId()]],
            'companies' => ['company', []],
            'tracking' => ['workplace_training_tracking_list', ['academicYear' => $academicYear]],
            'calendar' => ['workplace_training_tracking_calendar_list', ['id' => $this->enrollmentOf($organizationCode)->getId()]],
            'visits' => ['workplace_training_visit_teacher_list', ['academicYear' => $academicYear]],
            'expenses' => ['workplace_training_travel_expense_teacher_list', ['academicYear' => $academicYear]],
            'final-report' => ['workplace_training_final_report_list', ['academicYear' => $academicYear]],
        };
    }

    private function enrollmentOf(string $organizationCode): AgreementEnrollment
    {
        $prefix = $organizationCode === DemoData::ORG_A_CODE ? DemoData::ORG_A : DemoData::ORG_B;
        $student = static::getContainer()->get('doctrine')->getRepository(StudentEnrollment::class)
            ->createQueryBuilder('se')->join('se.person', 'p')
            ->where('p.loginUsername = :u')->setParameter('u', DemoData::username($prefix, DemoData::ROLE_STUDENT_1))
            ->getQuery()->getSingleResult();
        $enrollment = static::getContainer()->get('doctrine')->getRepository(AgreementEnrollment::class)
            ->findOneBy(['studentEnrollment' => $student]);
        self::assertInstanceOf(AgreementEnrollment::class, $enrollment);

        return $enrollment;
    }

    /**
     * @return iterable<string, array{string, string, int}>
     */
    public static function accessProvider(): iterable
    {
        foreach (self::ACCESS as $screen => $allowed) {
            foreach (array_merge(self::ADMINS, self::ALL_ROLES) as $user) {
                $granted = in_array($user, self::ADMINS, true) || in_array($user, $allowed, true);
                yield "$screen / $user" => [$screen, $user, $granted ? 200 : 403];
            }
        }
    }

    /**
     * @dataProvider accessProvider
     */
    public function testAccessDependsOnTheRole(string $screen, string $username, int $expectedStatus): void
    {
        [$name, $parameters] = $this->route($screen, DemoData::ORG_A_CODE);
        $this->login($this->getUser($username));
        $this->client->request('GET', static::getContainer()->get('router')->generate($name, $parameters));

        self::assertResponseStatusCodeSame($expectedStatus);
    }

    public function testLocalAdministratorSeesShiftsAndAgreements(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        [$name, $parameters] = $this->route('shifts', DemoData::ORG_A_CODE);
        $this->client->request('GET', static::getContainer()->get('router')->generate($name, $parameters));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Formación en centros de trabajo', $this->client->getResponse()->getContent());

        [$name, $parameters] = $this->route('agreements', DemoData::ORG_A_CODE);
        $this->client->request('GET', static::getContainer()->get('router')->generate($name, $parameters));
        self::assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('Tecnologías del Sur S.L. - Sede central', $content);
        self::assertStringContainsString('Consultoría del Norte S.A. - Oficina de Úbeda', $content);
        self::assertStringContainsString('Encontrados 3 resultados', $content);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function screenProvider(): iterable
    {
        foreach (array_keys(self::ACCESS) as $screen) {
            yield $screen => [$screen];
        }
    }

    /**
     * Un administrador local de la organización B no debe acceder a los datos de la organización A
     * aunque conozca los identificadores.
     *
     * @dataProvider screenProvider
     */
    public function testOtherOrganizationAdministratorCannotAccessOrganizationAData(string $screen): void
    {
        if ($screen === 'home' || $screen === 'companies') {
            $this->markTestSkipped('Pantalla sin identificadores de la organización');
        }
        [$name, $parameters] = $this->route($screen, DemoData::ORG_A_CODE);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->client->request('GET', static::getContainer()->get('router')->generate($name, $parameters));

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @dataProvider screenProvider
     */
    public function testOtherOrganizationTutorCannotAccessOrganizationAData(string $screen): void
    {
        if ($screen === 'home' || $screen === 'companies') {
            $this->markTestSkipped('Pantalla sin identificadores de la organización');
        }
        [$name, $parameters] = $this->route($screen, DemoData::ORG_A_CODE);
        $this->login($this->getUser('beta.tutor'), true, DemoData::ORG_B_CODE);
        $this->client->request('GET', static::getContainer()->get('router')->generate($name, $parameters));

        self::assertResponseStatusCodeSame(403);
    }

    public function testOrganizationBSeesItsOwnWptData(): void
    {
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        [$name, $parameters] = $this->route('agreements', DemoData::ORG_B_CODE);
        $this->client->request('GET', static::getContainer()->get('router')->generate($name, $parameters));

        self::assertResponseIsSuccessful();
        $content = $this->client->getResponse()->getContent();
        self::assertStringContainsString('Tecnologías del Sur S.L. - Sede central', $content);
        self::assertStringContainsString('Estudiante Beta', $content);
        self::assertStringNotContainsString('Estudiante Alfa', $content);
    }
}
