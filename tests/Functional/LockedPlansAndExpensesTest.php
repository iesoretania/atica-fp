<?php

namespace App\Tests\Functional;

use Doctrine\DBAL\Connection;

/**
 * Bloqueo de proyectos de FP dual y de planes de formación de la FFEOE (solo la administración y, en FP dual, la
 * coordinación pueden cambiarlos) y gestión de los gastos de desplazamiento de cualquier docente por la jefatura, la
 * coordinación o la gestión de la FFEOE y por el responsable económico en los tres módulos.
 */
class LockedPlansAndExpensesTest extends AppWebTestCase
{
    private function db(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    private function teacherId(string $username): int
    {
        return (int) $this->db()->fetchOne(
            'SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ? ORDER BY t.id DESC LIMIT 1',
            [$username]
        );
    }

    // --- Bloqueo del plan de formación de la FFEOE ----------------------------------------------

    /**
     * @return iterable<string, array{string, int, int}> usuario, estado con el plan abierto y estado con el plan bloqueado
     */
    public static function planManagerProvider(): iterable
    {
        yield 'administración del centro' => ['alfa.admin', 200, 200];
        yield 'jefatura de departamento' => ['alfa.jefe', 200, 403];
        yield 'gestión de la FFEOE' => ['alfa.gestorffe', 200, 403];
    }

    /**
     * @dataProvider planManagerProvider
     */
    public function testLockedTrainingPlanIsOnlyManagedByTheAdministration(string $username, int $open, int $locked): void
    {
        $plan = (int) $this->db()->fetchOne('SELECT id FROM itp_training_program ORDER BY id LIMIT 1');
        $grade = (int) $this->db()->fetchOne('SELECT id FROM itp_program_grade WHERE training_program_id = ? ORDER BY id LIMIT 1', [$plan]);
        $urls = [
            "/formacion/plan/$plan/" . $this->year(),
            "/formacion/plan/actividad/listar/$grade/1",
            "/formacion/plan/empresa/listar/$grade/1",
        ];
        $this->login($this->getUser($username));

        foreach ($urls as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame($open, "$username $url con el plan abierto");
        }

        $this->db()->executeStatement('UPDATE itp_training_program SET locked = 1 WHERE id = ?', [$plan]);
        foreach ($urls as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame($locked, "$username $url con el plan bloqueado");
        }
    }

    // --- Bloqueo del proyecto de FP dual ---------------------------------------------------------

    /**
     * @return iterable<string, array{string, int}> usuario y estado con el proyecto bloqueado
     */
    public static function projectManagerProvider(): iterable
    {
        yield 'administración del centro' => ['alfa.admin', 200];
        yield 'coordinación del proyecto' => ['alfa.coordinador', 200];
        yield 'jefatura de departamento' => ['alfa.jefe', 403];
    }

    /**
     * @dataProvider projectManagerProvider
     */
    public function testLockedProjectIsNotManagedByTheDepartmentHead(string $username, int $locked): void
    {
        $project = (int) $this->db()->fetchOne('SELECT id FROM wlt_project ORDER BY id LIMIT 1');
        $urls = ["/dual/proyecto/$project", "/dual/proyecto/programa/$project/actividad/1"];
        $this->login($this->getUser($username));

        foreach ($urls as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(200, "$username $url con el proyecto abierto");
        }

        $this->db()->executeStatement('UPDATE wlt_project SET locked = 1 WHERE id = ?', [$project]);
        foreach ($urls as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame($locked, "$username $url con el proyecto bloqueado");
        }
    }

    // --- Gastos de cualquier docente -------------------------------------------------------------

    /**
     * @return iterable<string, array{string, string, bool}> módulo, usuario y si puede registrar y modificar gastos ajenos
     */
    public static function expenseManagerProvider(): iterable
    {
        foreach (['fct' => 'FCT', 'dual' => 'FP dual', 'formacion' => 'FFEOE'] as $path => $module) {
            yield "$module: administración" => [$path, 'alfa.admin', true];
            yield "$module: responsable económico" => [$path, 'alfa.economico', true];
            yield "$module: jefatura" => [$path, 'alfa.jefe', true];
            yield "$module: otro docente" => [$path, 'alfa.profesor', false];
        }
        yield 'FP dual: coordinación' => ['dual', 'alfa.coordinador', true];
        yield 'FFEOE: gestión' => ['formacion', 'alfa.gestorffe', true];
    }

    /**
     * @dataProvider expenseManagerProvider
     */
    public function testExpensesOfAnyTeacherAreManagedByTheManagers(string $path, string $username, bool $allowed): void
    {
        // los gastos de las fixtures son del docente «tutor» en FCT y FP dual y de «profesor» en FFEOE
        $teacher = $this->teacherId($path === 'formacion' ? 'alfa.profesor' : 'alfa.tutor');
        // «otro docente» es quien no es dueño de esos gastos
        if ($username === 'alfa.profesor' && $path === 'formacion') {
            $username = 'alfa.tutor';
        }
        $this->login($this->getUser($username));

        $this->client->request('GET', "/$path/desplazamiento/nuevo/$teacher");
        self::assertResponseStatusCodeSame($allowed ? 200 : 403, "$username: registrar un gasto de otro docente");

        $table = ['fct' => 'wpt_travel_expense', 'dual' => 'wlt_travel_expense', 'formacion' => 'itp_travel_expense'][$path];
        $expense = (int) $this->db()->fetchOne("SELECT id FROM $table ORDER BY id LIMIT 1");
        if ($expense === 0) {
            return;
        }
        $crawler = $this->client->request('GET', "/$path/desplazamiento/detalle/$expense");
        if ($this->client->getResponse()->getStatusCode() === 200) {
            self::assertSame($allowed ? 1 : 0, $crawler->selectButton('Guardar cambios')->count(), "$username: modificar el gasto de otro docente");
        }
    }

    // --- Desplegable «Docente» de una visita ajena -----------------------------------------------

    public function testDepartmentHeadSeesTheTeacherWhoRegisteredAForeignFfeoeVisit(): void
    {
        $visit = (int) $this->db()->fetchOne('SELECT id FROM itp_contact ORDER BY id LIMIT 1');
        $owner = (int) $this->db()->fetchOne('SELECT teacher_id FROM itp_contact WHERE id = ?', [$visit]);
        $this->login($this->getUser('alfa.jefe'));

        $crawler = $this->client->request('GET', "/formacion/contacto/$visit");

        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->selectButton('Guardar cambios')->count());
        self::assertSame((string) $owner, $crawler->filter('select[name="contact[teacher]"] option[selected]')->attr('value'));
    }
}
