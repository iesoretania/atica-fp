<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\Project;
use App\Tests\Fixtures\DemoData;

/**
 * Módulo de FP dual: qué ve cada rol y aislamiento entre organizaciones.
 * Las expectativas se derivan de los Voters de src/Security/WltModule.
 */
class WltPagesTest extends AppWebTestCase
{
    private const ADMINS = [DemoData::USER_ADMIN, 'alfa.admin'];

    private const ALL_ROLES = [
        'alfa.director', 'alfa.economico', 'alfa.jefe', 'alfa.coordinador', 'alfa.tutor', 'alfa.profesor',
        'alfa.alumno1', 'alfa.alumno4', 'alfa.alumno5', 'alfa.tutorlaboral', 'alfa.tutorlaboral2',
    ];

    /**
     * Roles de la organización A (además de los administradores) que pueden acceder a cada pantalla.
     * "alumno1" solo participa en FCT y "alumno5" en FP dual: el módulo no debe estar abierto al otro.
     */
    private const ACCESS = [
        'home' => ['alfa.jefe', 'alfa.coordinador', 'alfa.tutor', 'alfa.profesor', 'alfa.alumno5', 'alfa.tutorlaboral', 'alfa.tutorlaboral2'],
        // gestión del proyecto: coordinador y jefatura de departamento
        'projects' => ['alfa.jefe', 'alfa.coordinador'],
        'agreements' => ['alfa.jefe', 'alfa.coordinador'],
        'program' => ['alfa.jefe', 'alfa.coordinador'],
        'attendance-report' => ['alfa.jefe', 'alfa.coordinador'],
        'tracking' => ['alfa.jefe', 'alfa.coordinador', 'alfa.tutor', 'alfa.profesor', 'alfa.alumno5', 'alfa.tutorlaboral', 'alfa.tutorlaboral2'],
        // calendario del convenio DUAL-1: su alumno, su tutor laboral y su tutor docente
        'calendar' => ['alfa.jefe', 'alfa.coordinador', 'alfa.tutor', 'alfa.alumno5', 'alfa.tutorlaboral'],
        'visits' => ['alfa.jefe', 'alfa.coordinador', 'alfa.tutor', 'alfa.profesor'],
        'meetings' => ['alfa.jefe', 'alfa.coordinador', 'alfa.tutor', 'alfa.profesor'],
        'evaluation' => ['alfa.jefe', 'alfa.coordinador', 'alfa.tutor', 'alfa.profesor', 'alfa.tutorlaboral', 'alfa.tutorlaboral2'],
        'results' => ['alfa.jefe', 'alfa.coordinador', 'alfa.tutor'],
        'expenses' => ['alfa.jefe', 'alfa.coordinador', 'alfa.economico', 'alfa.tutor', 'alfa.profesor'],
    ];

    /**
     * @return array{string, array<string, mixed>}
     */
    private function route(string $screen, string $organizationCode): array
    {
        $doctrine = static::getContainer()->get('doctrine');
        $organization = $this->getOrganization($organizationCode);
        $academicYear = $organization->getCurrentAcademicYear()->getId();
        $project = $doctrine->getRepository(Project::class)->findOneBy(['organization' => $organization]);
        self::assertInstanceOf(Project::class, $project);
        $agreement = $doctrine->getRepository(Agreement::class)->findOneBy(['project' => $project, 'internalCode' => 'DUAL-1']);
        self::assertInstanceOf(Agreement::class, $agreement);

        return match ($screen) {
            'home' => ['work_linked_training', []],
            'projects' => ['work_linked_training_project_list', ['academicYear' => $academicYear]],
            'agreements' => ['work_linked_training_agreement_list', ['id' => $project->getId()]],
            'program' => ['work_linked_training_learning_program_list', ['project' => $project->getId()]],
            'attendance-report' => ['work_linked_training_report_attendance_list', ['academicYear' => $academicYear]],
            'tracking' => ['work_linked_training_tracking_list', ['academicYear' => $academicYear]],
            'calendar' => ['work_linked_training_tracking_calendar_list', ['id' => $agreement->getId()]],
            'visits' => ['work_linked_training_contact_list', ['academicYear' => $academicYear]],
            'meetings' => ['work_linked_training_meeting_list', ['academicYear' => $academicYear]],
            'evaluation' => ['work_linked_training_evaluation_list', ['academicYear' => $academicYear]],
            'results' => ['work_linked_training_evaluation_summary_list', ['academicYear' => $academicYear]],
            'expenses' => ['work_linked_training_travel_expense_teacher_list', ['academicYear' => $academicYear]],
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

    public function testProjectManagerSeesTheProjectAndItsAgreements(): void
    {
        $this->login($this->getUser('alfa.coordinador'));

        $content = $this->get('projects');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Proyecto de FP dual en Desarrollo de Aplicaciones', $content);

        $content = $this->get('agreements');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Tecnologías del Sur S.L. - Sede central', $content);
        self::assertStringContainsString('Consultoría del Norte S.A. - Oficina de Úbeda', $content);
        self::assertStringContainsString('Estudiante Alfa', $content);
        self::assertStringNotContainsString('Estudiante Beta', $content);
    }

    public function testWorkTutorOnlySeesOwnAgreementInTheTrackingList(): void
    {
        $this->login($this->getUser('alfa.tutorlaboral2'));
        $content = $this->get('tracking');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Alumno 6', $content);
        self::assertStringNotContainsString('Alumno 5', $content);
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
        foreach (['beta.admin', 'beta.coordinador', 'beta.tutor'] as $username) {
            static::ensureKernelShutdown();
            $this->client = static::createClient();
            $this->login($this->getUser($username), true, DemoData::ORG_B_CODE);
            $this->get($screen, DemoData::ORG_A_CODE);

            self::assertResponseStatusCodeSame(403, "$username en $screen");
        }
    }

    public function testOrganizationBSeesOnlyItsOwnDualData(): void
    {
        $this->login($this->getUser('beta.coordinador'), true, DemoData::ORG_B_CODE);
        $content = $this->get('agreements', DemoData::ORG_B_CODE);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Estudiante Beta', $content);
        self::assertStringNotContainsString('Estudiante Alfa', $content);
    }
}
