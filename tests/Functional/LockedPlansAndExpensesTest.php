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
     * @return iterable<string, array{string, bool}> usuario y si puede seguir modificando con el plan bloqueado
     */
    public static function planManagerProvider(): iterable
    {
        yield 'administración del centro' => ['alfa.admin', true];
        yield 'jefatura de departamento' => ['alfa.jefe', false];
        yield 'gestión de la FFEOE' => ['alfa.gestorffe', false];
    }

    /**
     * Con el plan bloqueado, la jefatura y la gestión consultan sus pantallas en solo lectura (sin botón de guardar y con
     * los campos desactivados) y no pueden escribir; la administración sigue pudiendo
     *
     * @dataProvider planManagerProvider
     */
    public function testLockedTrainingPlanIsReadOnlyExceptForTheAdministration(string $username, bool $canModify): void
    {
        $db = $this->db();
        $plan = (int) $db->fetchOne('SELECT id FROM itp_training_program ORDER BY id LIMIT 1');
        $grade = (int) $db->fetchOne('SELECT id FROM itp_program_grade WHERE training_program_id = ? ORDER BY id LIMIT 1', [$plan]);
        $forms = [
            "/formacion/plan/$plan/" . $this->year(),
            '/formacion/plan/grupo/' . (int) $db->fetchOne('SELECT id FROM itp_program_group WHERE program_grade_id = ? LIMIT 1', [$grade]),
            '/formacion/plan/actividad/' . (int) $db->fetchOne('SELECT id FROM itp_activity WHERE program_grade_id = ? LIMIT 1', [$grade]),
            '/formacion/plan/empresa/' . (int) $db->fetchOne('SELECT id FROM itp_company_program WHERE program_grade_id = ? LIMIT 1', [$grade]),
            '/formacion/plan/curso/grupo/gestion/detalle/' . (int) $db->fetchOne('SELECT id FROM itp_student_program_workcenter ORDER BY id LIMIT 1'),
        ];
        $deletion = ["/formacion/plan/actividad/eliminar/$grade", ['items' => [(int) $db->fetchOne('SELECT id FROM itp_activity WHERE program_grade_id = ? LIMIT 1', [$grade])]]];
        $this->login($this->getUser($username));

        // abierto: todos pueden modificar y la confirmación de borrado se muestra
        foreach ($forms as $url) {
            $crawler = $this->client->request('GET', $url);
            self::assertResponseIsSuccessful("$username $url con el plan abierto");
            self::assertSame(1, $crawler->selectButton('Guardar cambios')->count(), "$username $url con el plan abierto");
        }
        $this->client->request('POST', $deletion[0], $deletion[1]);
        self::assertResponseIsSuccessful();

        $db->executeStatement('UPDATE itp_training_program SET locked = 1 WHERE id = ?', [$plan]);
        foreach ($forms as $url) {
            $crawler = $this->client->request('GET', $url);
            self::assertResponseIsSuccessful("$username $url con el plan bloqueado");
            self::assertSame($canModify ? 1 : 0, $crawler->selectButton('Guardar cambios')->count(), "$username $url con el plan bloqueado");
            if (!$canModify) {
                self::assertSame(0, $crawler->filter('main input:not([disabled]):not([type=hidden]):not([type=search]), main select:not([disabled]), main textarea:not([disabled])')->count(), "$username $url: campos activos con el plan bloqueado");
            }
        }
        $this->client->request('POST', $deletion[0], $deletion[1] + ['confirm' => 'ok']);
        self::assertResponseStatusCodeSame($canModify ? 302 : 403, "$username: borrar con el plan bloqueado");
        $this->client->request('POST', $forms[0], ['training_program' => ['name' => 'Cambiado']]);
        if (!$canModify) {
            self::assertResponseStatusCodeSame(403);
        }
        self::assertNotSame('Cambiado', $db->fetchOne('SELECT name FROM itp_training_program WHERE id = ?', [$plan]));
    }

    // --- Bloqueo del proyecto de FP dual ---------------------------------------------------------

    /**
     * @return iterable<string, array{string, bool}> usuario y si puede seguir modificando con el proyecto bloqueado
     */
    public static function projectManagerProvider(): iterable
    {
        yield 'administración del centro' => ['alfa.admin', true];
        yield 'coordinación del proyecto' => ['alfa.coordinador', true];
        yield 'jefatura de departamento' => ['alfa.jefe', false];
    }

    /**
     * Con el proyecto bloqueado, la jefatura consulta el proyecto, sus actividades, concreciones y empresas en solo
     * lectura y no puede escribir; la administración y la coordinación siguen pudiendo
     *
     * @dataProvider projectManagerProvider
     */
    public function testLockedProjectIsReadOnlyForTheDepartmentHead(string $username, bool $canModify): void
    {
        $db = $this->db();
        $project = (int) $db->fetchOne('SELECT id FROM wlt_project ORDER BY id LIMIT 1');
        $activity = (int) $db->fetchOne('SELECT id FROM wlt_activity WHERE project_id = ? ORDER BY id LIMIT 1', [$project]);
        $forms = [
            "/dual/proyecto/$project",
            "/dual/proyecto/estudiantes/$project",
            "/dual/proyecto/programa/actividad/$activity",
            '/dual/proyecto/programa/' . (int) $db->fetchOne('SELECT id FROM wlt_activity_realization WHERE activity_id = ? ORDER BY id LIMIT 1', [$activity]) . '/detalles/concrecion',
            '/dual/programa/' . (int) $db->fetchOne('SELECT id FROM wlt_learning_program WHERE project_id = ? ORDER BY id LIMIT 1', [$project]),
        ];
        $this->login($this->getUser($username));

        foreach ($forms as $url) {
            $crawler = $this->client->request('GET', $url);
            self::assertResponseIsSuccessful("$username $url con el proyecto abierto");
            self::assertSame(1, $crawler->selectButton('Guardar cambios')->count(), "$username $url con el proyecto abierto");
        }
        $this->client->request('POST', "/dual/proyecto/programa/$project/actividad/eliminar", ['items' => [$activity]]);
        self::assertResponseIsSuccessful();

        $db->executeStatement('UPDATE wlt_project SET locked = 1 WHERE id = ?', [$project]);
        foreach ($forms as $url) {
            $crawler = $this->client->request('GET', $url);
            self::assertResponseIsSuccessful("$username $url con el proyecto bloqueado");
            self::assertSame($canModify ? 1 : 0, $crawler->selectButton('Guardar cambios')->count(), "$username $url con el proyecto bloqueado");
        }
        $this->client->request('POST', "/dual/proyecto/programa/$project/actividad/eliminar", ['items' => [$activity], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame($canModify ? 302 : 403, "$username: borrar con el proyecto bloqueado");
        $this->client->request('GET', "/dual/proyecto/programa/$project/actividad/1");
        self::assertResponseIsSuccessful();
        if (!$canModify) {
            self::assertSame(1, (int) $db->fetchOne('SELECT COUNT(*) FROM wlt_activity WHERE id = ?', [$activity]));
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
