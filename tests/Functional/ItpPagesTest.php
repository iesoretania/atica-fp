<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Tests\Fixtures\DemoData;

/**
 * Módulo de Formación en Empresas (FFE): qué ve cada rol y aislamiento entre organizaciones.
 * Las expectativas se derivan de los Voters de src/Security/ItpModule.
 */
class ItpPagesTest extends AppWebTestCase
{
    private const ADMINS = [DemoData::USER_ADMIN, 'alfa.admin'];

    private const ALL_ROLES = [
        'alfa.coordinador', 'alfa.director', 'alfa.economico', 'alfa.jefe', 'alfa.gestorffe', 'alfa.tutor',
        'alfa.profesor', 'alfa.alumno1', 'alfa.alumno4', 'alfa.alumno7', 'alfa.alumno8',
        'alfa.tutorlaboral', 'alfa.tutorlaboral2',
    ];

    private const PARTICIPANTS = [
        'alfa.jefe', 'alfa.gestorffe', 'alfa.tutor', 'alfa.profesor', 'alfa.alumno7', 'alfa.alumno8',
        'alfa.tutorlaboral', 'alfa.tutorlaboral2',
    ];

    // el responsable económico entra al módulo para consultar los gastos de desplazamiento
    private const WITH_FINANCIAL_MANAGER = [...self::PARTICIPANTS, 'alfa.economico'];

    /**
     * Roles de la organización A (además de los administradores) que pueden acceder a cada pantalla.
     * El coordinador de FP dual y el alumnado de FCT o dual no tienen acceso a este módulo.
     */
    private const ACCESS = [
        'home' => self::WITH_FINANCIAL_MANAGER,
        // gestión del programa: gestor de la FFE y jefatura de departamento
        'programs' => ['alfa.jefe', 'alfa.gestorffe'],
        'grades' => ['alfa.jefe', 'alfa.gestorffe'],
        'groups' => ['alfa.jefe', 'alfa.gestorffe'],
        'companies' => ['alfa.jefe', 'alfa.gestorffe'],
        'students' => ['alfa.jefe', 'alfa.gestorffe'],
        'manage' => ['alfa.jefe', 'alfa.gestorffe'],
        // sigue sin ver datos del alumnado: sus listados salen vacíos (ver testFinancialManagerDoesNotSeeStudentData)
        'tracking' => self::WITH_FINANCIAL_MANAGER,
        'grading' => self::WITH_FINANCIAL_MANAGER,
        // estancia de alumno7: su tutor docente (profesor), la tutora del grupo, él mismo y su tutor laboral
        'calendar' => ['alfa.jefe', 'alfa.gestorffe', 'alfa.tutor', 'alfa.profesor', 'alfa.alumno7', 'alfa.tutorlaboral'],
        // visitas: tutores docentes y gestión
        'visits' => ['alfa.jefe', 'alfa.gestorffe', 'alfa.tutor', 'alfa.profesor'],
        // gastos de desplazamiento: además, el responsable económico (ITP_ACCESS_EXPENSE)
        'expenses' => ['alfa.jefe', 'alfa.gestorffe', 'alfa.economico', 'alfa.tutor', 'alfa.profesor'],
    ];

    /**
     * @return array{string, array<string, mixed>}
     */
    private function route(string $screen, string $organizationCode): array
    {
        $doctrine = static::getContainer()->get('doctrine');
        $academicYear = $this->getOrganization($organizationCode)->getCurrentAcademicYear()->getId();
        $programGrade = $doctrine->getRepository(ProgramGrade::class)->createQueryBuilder('pg')
            ->join('pg.grade', 'g')->join('g.training', 't')
            ->where('t.academicYear = :ay')->setParameter('ay', $academicYear)
            ->setMaxResults(1)->getQuery()->getSingleResult();
        $programGroup = $doctrine->getRepository(ProgramGroup::class)->findOneBy(['programGrade' => $programGrade]);
        $prefix = $organizationCode === DemoData::ORG_A_CODE ? DemoData::ORG_A : DemoData::ORG_B;
        $stay = $doctrine->getRepository(StudentProgramWorkcenter::class)->createQueryBuilder('s')
            ->join('s.studentProgram', 'sp')->join('sp.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :u')->setParameter('u', DemoData::username($prefix, DemoData::ROLE_STUDENT_7))
            ->getQuery()->getSingleResult();

        return match ($screen) {
            'home' => ['in_company_training_phase', []],
            'programs' => ['in_company_training_phase_training_program_list', ['academicYear' => $academicYear]],
            'grades' => ['in_company_training_phase_grade_list', ['trainingProgram' => $programGrade->getTrainingProgram()->getId()]],
            'groups' => ['in_company_training_phase_group_list', ['programGrade' => $programGrade->getId()]],
            'companies' => ['in_company_training_phase_company_list', ['programGrade' => $programGrade->getId()]],
            'students' => ['in_company_training_phase_student_program_list', ['programGroup' => $programGroup->getId()]],
            'manage' => ['in_company_training_phase_student_program_workcenter_manage_list', ['programGrade' => $programGrade->getId()]],
            'tracking' => ['in_company_training_phase_tracking_list', ['academicYear' => $academicYear]],
            'grading' => ['in_company_training_phase_tracking_grading_list', ['academicYear' => $academicYear]],
            'calendar' => ['in_company_training_phase_tracking_calendar_list', ['studentProgramWorkcenter' => $stay->getId()]],
            'visits' => ['in_company_training_phase_contact_list', ['academicYear' => $academicYear]],
            'expenses' => ['in_company_training_phase_travel_expense_teacher_list', ['academicYear' => $academicYear]],
        };
    }

    private function get(string $screen, string $organizationCode = DemoData::ORG_A_CODE): string
    {
        [$name, $parameters] = $this->route($screen, $organizationCode);
        $this->client->request('GET', static::getContainer()->get('router')->generate($name, $parameters));

        return (string) $this->client->getResponse()->getContent();
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
        $this->login($this->getUser($username));
        $this->get($screen);

        self::assertResponseStatusCodeSame($expectedStatus);
    }

    public function testManagerSeesTheProgramGroupAndStudents(): void
    {
        $this->login($this->getUser('alfa.gestorffe'));

        self::assertStringContainsString('Programa de formación en empresas de DAM', $this->get('programs'));
        self::assertResponseIsSuccessful();

        self::assertStringContainsString('2º DAM A', $this->get('groups'));
        self::assertResponseIsSuccessful();

        $students = $this->get('students');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Estudiante Alfa, Alumno 7', $students);
        self::assertStringContainsString('Estudiante Alfa, Alumno 8', $students);
        self::assertStringNotContainsString('Estudiante Beta', $students);

        self::assertStringContainsString('Tecnologías del Sur S.L.', $this->get('companies'));
        self::assertResponseIsSuccessful();
    }

    public function testFinancialManagerSeesTheTravelExpensesOfAllTeachers(): void
    {
        $this->login($this->getUser('alfa.economico'));
        $content = $this->get('expenses');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Encontrados 2 resultados', $content);
        self::assertStringContainsString('Docente Alfa', $content);
        self::assertStringContainsString('Tutor Segundo Alfa', $content);
        self::assertStringNotContainsString('Beta', str_replace('I.E.S. Alfa', '', $content));
    }

    public function testFinancialManagerDoesNotSeeStudentData(): void
    {
        $this->login($this->getUser('alfa.economico'));

        foreach (['tracking', 'grading'] as $screen) {
            $content = $this->get($screen);
            self::assertResponseIsSuccessful();
            self::assertStringContainsString('No hay resultados', $content, $screen);
            self::assertStringNotContainsString('Estudiante Alfa', $content, $screen);
        }

        // la exportación a CSV tampoco debe incluir al alumnado
        $academicYear = $this->getOrganization()->getCurrentAcademicYear()->getId();
        $this->client->request('GET', static::getContainer()->get('router')->generate(
            'in_company_training_phase_tracking_export',
            ['academicYear' => $academicYear]
        ));
        self::assertResponseIsSuccessful();
        // es una respuesta en streaming: el contenido está en la respuesta interna del cliente
        $csv = (string) $this->client->getInternalResponse()->getContent();
        self::assertNotSame('', $csv, 'El CSV está vacío: la comprobación no sería concluyente');
        self::assertStringNotContainsString('Estudiante Alfa', $csv);
        self::assertStringNotContainsString('Alumno', $csv);
    }

    /**
     * Control positivo de la prueba anterior: quien gestiona el módulo sí exporta al alumnado
     */
    public function testManagerExportsTheStudentsToCsv(): void
    {
        $this->login($this->getUser('alfa.gestorffe'));
        $this->client->request('GET', static::getContainer()->get('router')->generate(
            'in_company_training_phase_tracking_export',
            ['academicYear' => $this->getOrganization()->getCurrentAcademicYear()->getId()]
        ));

        self::assertResponseIsSuccessful();
        $csv = (string) $this->client->getInternalResponse()->getContent();
        self::assertStringContainsString('Alumno 7', $csv);
        self::assertStringContainsString('Alumno 8', $csv);
    }

    public function testWorkTutorOnlySeesOwnStayInTheTrackingList(): void
    {
        $this->login($this->getUser('alfa.tutorlaboral2'));
        $content = $this->get('tracking');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Alumno 8', $content);
        self::assertStringNotContainsString('Alumno 7', $content);
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
     * @dataProvider screenProvider
     */
    public function testOtherOrganizationUsersCannotAccessOrganizationAData(string $screen): void
    {
        if ($screen === 'home') {
            $this->markTestSkipped('Pantalla sin identificadores de la organización');
        }
        foreach (['beta.admin', 'beta.gestorffe', 'beta.tutor'] as $username) {
            static::ensureKernelShutdown();
            $this->client = static::createClient();
            $this->login($this->getUser($username), true, DemoData::ORG_B_CODE);
            $this->get($screen, DemoData::ORG_A_CODE);

            self::assertResponseStatusCodeSame(403, "$username en $screen");
        }
    }

    public function testOrganizationBSeesOnlyItsOwnFfeData(): void
    {
        $this->login($this->getUser('beta.gestorffe'), true, DemoData::ORG_B_CODE);
        $content = $this->get('students', DemoData::ORG_B_CODE);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Estudiante Beta', $content);
        self::assertStringNotContainsString('Estudiante Alfa', $content);
    }
}
