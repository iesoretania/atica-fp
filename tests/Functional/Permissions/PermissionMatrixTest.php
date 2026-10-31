<?php

namespace App\Tests\Functional\Permissions;

use App\Tests\Functional\AppWebTestCase;
use Doctrine\DBAL\Connection;

/**
 * Comprueba que lo que cada perfil puede hacer en cada módulo coincide con las matrices de permisos del manual
 * de usuario (ver PermissionMatrix). Una diferencia significa una de dos cosas: o se ha cambiado un permiso sin
 * revisar el manual, o el manual estaba mal. En ambos casos hay que decidir cuál es lo correcto antes de
 * actualizar la instantánea.
 *
 * Para regenerar las instantáneas tras un cambio intencionado:
 *
 *   UPDATE_PERMISSION_MATRIX=1 APP_ENV=test php bin/phpunit tests/Functional/Permissions
 *
 * y después revisar el diff de snapshots/*.json y actualizar los capítulos del manual
 * (`php scripts/render-permission-matrix.php <módulo>` genera la tabla).
 */
class PermissionMatrixTest extends AppWebTestCase
{
    private const SNAPSHOTS = __DIR__ . '/snapshots';

    /** @var array<string, array<string, array<string, bool>>> módulo => perfil => petición => permitido */
    private static array $collected = [];

    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private static function updating(): bool
    {
        return getenv('UPDATE_PERMISSION_MATRIX') === '1';
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function profileProvider(): iterable
    {
        foreach (PermissionMatrix::modules() as $module => $definition) {
            foreach (array_keys($definition['profiles']) as $profile) {
                yield "$module: $profile" => [$module, $profile];
            }
        }
    }

    /**
     * Valores de las fixtures que sustituyen a los marcadores de las peticiones
     *
     * @return array<string, int|string>
     */
    private function placeholders(): array
    {
        $db = $this->connection();
        $organization = $this->getOrganization();

        $enrollment = (int) $db->fetchOne(
            'SELECT ae.id FROM wpt_agreement_enrollment ae JOIN edu_student_enrollment se ON se.id = ae.student_enrollment_id JOIN person p ON p.id = se.person_id WHERE p.login_username = ?',
            ['alfa.alumno1']
        );

        // las fixtures no tienen encuestas propias del centro: se crea una (el test se revierte al terminar)
        if (!$db->fetchOne('SELECT id FROM survey WHERE organization_id = ?', [$organization->getId()])) {
            $db->insert('survey', ['organization_id' => $organization->getId(), 'title' => 'Encuesta de prueba']);
        }

        // el profesorado del grupo de FP dual (alfa.director, sin otro cargo) da clase al alumno 5
        $db->executeStatement(
            'INSERT INTO edu_teaching (teacher_id, group_id, subject_id)
             SELECT t.id, se.group_id, (SELECT s.id FROM edu_subject s JOIN edu_group g ON g.grade_id = s.grade_id WHERE g.id = se.group_id LIMIT 1)
             FROM edu_teacher t
             JOIN person p ON p.id = t.person_id AND p.login_username = ?
             JOIN edu_student_enrollment se ON se.person_id = (SELECT id FROM person WHERE login_username = ?)
             WHERE t.academic_year_id = ? AND NOT EXISTS (SELECT 1 FROM edu_teaching x WHERE x.teacher_id = t.id)',
            ['alfa.director', 'alfa.alumno5', $organization->getCurrentAcademicYear()->getId()]
        );
        // lo mismo para el grupo de FFE (el del alumno 7), por si no es el mismo grupo
        $db->executeStatement(
            'INSERT INTO edu_teaching (teacher_id, group_id, subject_id)
             SELECT t.id, se.group_id, (SELECT s.id FROM edu_subject s JOIN edu_group g ON g.grade_id = s.grade_id WHERE g.id = se.group_id LIMIT 1)
             FROM edu_teacher t
             JOIN person p ON p.id = t.person_id AND p.login_username = ?
             JOIN edu_student_enrollment se ON se.person_id = (SELECT id FROM person WHERE login_username = ?)
             WHERE t.academic_year_id = ? AND NOT EXISTS (SELECT 1 FROM edu_teaching x WHERE x.teacher_id = t.id AND x.group_id = se.group_id)',
            ['alfa.director', 'alfa.alumno7', $organization->getCurrentAcademicYear()->getId()]
        );
        $dualAgreement = (int) $db->fetchOne(
            'SELECT a.id FROM wlt_agreement a JOIN edu_student_enrollment se ON se.id = a.student_enrollment_id JOIN person p ON p.id = se.person_id WHERE p.login_username = ?',
            ['alfa.alumno5']
        );

        // FFE: la estancia del alumno 7 (tutoría docente «alfa.profesor», tutoría laboral «alfa.tutorlaboral»)
        $stay = (int) $db->fetchOne(
            'SELECT spw.id FROM itp_student_program_workcenter spw JOIN itp_student_program sp ON sp.id = spw.student_program_id JOIN edu_student_enrollment se ON se.id = sp.student_enrollment_id JOIN person p ON p.id = se.person_id WHERE p.login_username = ?',
            ['alfa.alumno7']
        );
        $programGrade = (int) $db->fetchOne(
            'SELECT pg.id FROM itp_program_grade pg JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id WHERE t.academic_year_id = ? ORDER BY pg.id LIMIT 1',
            [$organization->getCurrentAcademicYear()->getId()]
        );
        $itpTeacher = (int) $db->fetchOne('SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', ['alfa.profesor']);

        return [
            // FFE
            '{trainingProgram}' => (int) $db->fetchOne('SELECT training_program_id FROM itp_program_grade WHERE id = ?', [$programGrade]),
            '{programGrade}' => $programGrade,
            '{programGroup}' => (int) $db->fetchOne('SELECT id FROM itp_program_group WHERE program_grade_id = ? ORDER BY id LIMIT 1', [$programGrade]),
            '{itpActivity}' => (int) $db->fetchOne('SELECT id FROM itp_activity WHERE program_grade_id = ? ORDER BY id LIMIT 1', [$programGrade]),
            '{companyProgram}' => (int) $db->fetchOne('SELECT id FROM itp_company_program WHERE program_grade_id = ? ORDER BY id LIMIT 1', [$programGrade]),
            '{stay}' => $stay,
            '{studentProgram}' => (int) $db->fetchOne('SELECT student_program_id FROM itp_student_program_workcenter WHERE id = ?', [$stay]),
            '{workDay}' => (int) $db->fetchOne('SELECT id FROM itp_work_day WHERE student_program_workcenter_id = ? ORDER BY id LIMIT 1', [$stay]),
            '{itpTeacher}' => $itpTeacher,
            '{itpContact}' => (int) $db->fetchOne('SELECT id FROM itp_contact WHERE teacher_id = ? ORDER BY id LIMIT 1', [$itpTeacher]),
            '{itpExpense}' => (int) $db->fetchOne('SELECT id FROM itp_travel_expense WHERE teacher_id = ? ORDER BY id LIMIT 1', [$itpTeacher]),
            // FP dual
            '{project}' => (int) $db->fetchOne('SELECT id FROM wlt_project WHERE organization_id = ? ORDER BY id LIMIT 1', [$organization->getId()]),
            '{learningProgram}' => (int) $db->fetchOne('SELECT lp.id FROM wlt_learning_program lp JOIN wlt_project pr ON pr.id = lp.project_id WHERE pr.organization_id = ? ORDER BY lp.id LIMIT 1', [$organization->getId()]),
            '{dualActivity}' => (int) $db->fetchOne('SELECT ac.id FROM wlt_activity ac JOIN wlt_project pr ON pr.id = ac.project_id WHERE pr.organization_id = ? ORDER BY ac.id LIMIT 1', [$organization->getId()]),
            '{realization}' => (int) $db->fetchOne('SELECT r.id FROM wlt_activity_realization r JOIN wlt_activity ac ON ac.id = r.activity_id JOIN wlt_project pr ON pr.id = ac.project_id WHERE pr.organization_id = ? ORDER BY r.id LIMIT 1', [$organization->getId()]),
            '{dualAgreement}' => $dualAgreement,
            '{dualStudent}' => (int) $db->fetchOne('SELECT se.id FROM edu_student_enrollment se JOIN person p ON p.id = se.person_id WHERE p.login_username = ?', ['alfa.alumno5']),
            '{meeting}' => (int) $db->fetchOne('SELECT m.id FROM wlt_meeting m JOIN wlt_project pr ON pr.id = m.project_id WHERE pr.organization_id = ? ORDER BY m.id LIMIT 1', [$organization->getId()]),
            '{dualContact}' => (int) $db->fetchOne('SELECT c.id FROM wlt_contact c JOIN edu_teacher t ON t.id = c.teacher_id JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ? ORDER BY c.id LIMIT 1', [$organization->getId()]),
            '{dualExpense}' => (int) $db->fetchOne('SELECT e.id FROM wlt_travel_expense e JOIN person p ON p.login_username = ? JOIN edu_teacher t ON t.id = e.teacher_id AND t.person_id = p.id ORDER BY e.id LIMIT 1', ['alfa.tutor']),
            // FCT
            '{year}' => $organization->getCurrentAcademicYear()->getId(),
            '{shift}' => (int) $db->fetchOne(
                'SELECT s.id FROM wpt_shift s JOIN edu_subject su ON su.id = s.subject_id JOIN edu_grade g ON g.id = su.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ? ORDER BY s.id LIMIT 1',
                [$organization->getId()]
            ),
            '{enrollment}' => $enrollment,
            '{agreement}' => (int) $db->fetchOne('SELECT agreement_id FROM wpt_agreement_enrollment WHERE id = ?', [$enrollment]),
            '{activity}' => (int) $db->fetchOne('SELECT a.id FROM wpt_activity a JOIN wpt_agreement ag ON ag.shift_id = a.shift_id JOIN wpt_agreement_enrollment ae ON ae.agreement_id = ag.id WHERE ae.id = ? ORDER BY a.id LIMIT 1', [$enrollment]),
            '{teacher}' => $teacher = (int) $db->fetchOne('SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', ['alfa.tutor']),
            '{visit}' => (int) $db->fetchOne('SELECT id FROM wpt_contact WHERE teacher_id = ? ORDER BY id LIMIT 1', [$teacher]),
            '{expense}' => (int) $db->fetchOne('SELECT id FROM wpt_travel_expense WHERE teacher_id = ? ORDER BY id LIMIT 1', [$teacher]),
            '{workTutor}' => (int) $db->fetchOne('SELECT id FROM person WHERE login_username = ?', ['alfa.tutorlaboral']),
            // parte general
            '{organization}' => $organization->getId(),
            '{person}' => (int) $db->fetchOne('SELECT id FROM person WHERE login_username = ?', ['alfa.tutor']),
            '{studentEnrollment}' => (int) $db->fetchOne('SELECT se.id FROM edu_student_enrollment se JOIN person p ON p.id = se.person_id WHERE p.login_username = ?', ['alfa.alumno1']),
            '{department}' => (int) $db->fetchOne('SELECT id FROM edu_department WHERE academic_year_id = ? ORDER BY id LIMIT 1', [$organization->getCurrentAcademicYear()->getId()]),
            '{training}' => (int) $db->fetchOne('SELECT id FROM edu_training WHERE academic_year_id = ? ORDER BY id LIMIT 1', [$organization->getCurrentAcademicYear()->getId()]),
            '{group}' => (int) $db->fetchOne('SELECT g.id FROM edu_group g JOIN edu_grade gr ON gr.id = g.grade_id JOIN edu_training t ON t.id = gr.training_id WHERE t.academic_year_id = ? ORDER BY g.id LIMIT 1', [$organization->getCurrentAcademicYear()->getId()]),
            '{subject}' => (int) $db->fetchOne('SELECT s.id FROM edu_subject s JOIN edu_grade gr ON gr.id = s.grade_id JOIN edu_training t ON t.id = gr.training_id WHERE t.academic_year_id = ? ORDER BY s.id LIMIT 1', [$organization->getCurrentAcademicYear()->getId()]),
            '{company}' => (int) $db->fetchOne('SELECT id FROM company ORDER BY id LIMIT 1'),
            '{workcenter}' => (int) $db->fetchOne('SELECT id FROM workcenter ORDER BY id LIMIT 1'),
            '{survey}' => (int) $db->fetchOne('SELECT id FROM survey WHERE organization_id = ? ORDER BY id LIMIT 1', [$organization->getId()]),
            '{template}' => (int) $db->fetchOne('SELECT id FROM edu_report_template WHERE organization_id = ? ORDER BY id LIMIT 1', [$organization->getId()]),
            '{route}' => (int) $db->fetchOne('SELECT id FROM edu_travel_route WHERE organization_id = ? ORDER BY id LIMIT 1', [$organization->getId()]),
            '{scale}' => (int) $db->fetchOne('SELECT id FROM edu_performance_scale WHERE organization_id = ? ORDER BY id LIMIT 1', [$organization->getId()]),
        ];
    }

    /**
     * @dataProvider profileProvider
     */
    public function testProfileMatchesTheManualMatrix(string $module, string $profile): void
    {
        $definition = PermissionMatrix::modules()[$module];
        $placeholders = $this->placeholders();
        $this->login($this->getUser($definition['profiles'][$profile]['user']));

        $actual = [];
        foreach ($definition['sections'] as $section) {
            foreach ($section['actions'] as $action) {
                [$method, $path, $body] = [...explode(' ', $action['request'], 3), ''];
                parse_str(strtr($body, $placeholders), $parameters);
                $this->client->request($method, strtr($path, $placeholders), $parameters);
                $status = $this->client->getResponse()->getStatusCode();

                $notFoundIsAllowed = ($action['notFoundIsAllowed'] ?? false) && $status === 404;
                self::assertTrue(
                    $status < 400 || $status === 403 || $notFoundIsAllowed,
                    sprintf('%s «%s» (%s) responde %d con el perfil %s: la acción no es válida', $module, $action['label'], $action['request'], $status, $profile)
                );
                $actual[$action['request']] = $status < 400 || $notFoundIsAllowed;
            }
        }

        self::$collected[$module][$profile] = $actual;
        if (self::updating()) {
            self::assertNotEmpty($actual);
            return;
        }

        $expected = self::expected($module, $profile);
        $differences = [];
        foreach ($actual as $request => $allowed) {
            if (($expected[$request] ?? null) !== $allowed) {
                $differences[] = sprintf('%s: el manual dice «%s» y el código «%s»', $request, self::word($expected[$request] ?? null), self::word($allowed));
            }
        }
        self::assertSame([], $differences, "El perfil $profile ($module) no coincide con la matriz del manual");
        self::assertSame(array_keys($expected), array_keys($actual), 'Las acciones de la matriz no coinciden con las de la instantánea');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function moduleProvider(): iterable
    {
        foreach (array_keys(PermissionMatrix::modules()) as $module) {
            yield $module => [$module];
        }
    }

    /**
     * La matriz solo comprueba quién llega a la pantalla de confirmación; este test comprueba que esa pantalla es
     * de verdad una confirmación: ninguna acción de borrado de la matriz modifica un solo dato sin «confirm=ok»,
     * ni siquiera cuando la pide la administración del centro
     *
     * @dataProvider moduleProvider
     */
    public function testNoDeleteActionChangesDataWithoutConfirmation(string $module): void
    {
        $definition = PermissionMatrix::modules()[$module];
        $placeholders = $this->placeholders();
        $this->login($this->getUser($definition['profiles']['admin']['user']));

        $checked = 0;
        foreach ($definition['sections'] as $section) {
            foreach ($section['actions'] as $action) {
                [$method, $path, $body] = [...explode(' ', $action['request'], 3), ''];
                if ($method !== 'POST' || !str_contains($body, 'items[]') && !str_contains($body, 'users[]')) {
                    continue;
                }
                $before = $this->tableCounts();
                parse_str(strtr($body, $placeholders), $parameters);
                $this->client->request($method, strtr($path, $placeholders), $parameters);

                $after = $this->tableCounts();
                $changed = array_keys(array_filter($after, static fn(int $rows, string $table): bool => $rows !== $before[$table], ARRAY_FILTER_USE_BOTH));
                self::assertSame([], $changed, sprintf('%s «%s» (%s) modifica datos sin pedir confirmación', $module, $action['label'], $action['request']));
                $checked++;
            }
        }

        self::assertGreaterThan(0, $checked, "El módulo $module no tiene acciones de borrado");
    }

    /**
     * @return array<string, int> filas de cada tabla
     */
    private function tableCounts(): array
    {
        $db = $this->connection();
        $counts = [];
        foreach ($db->createSchemaManager()->listTableNames() as $table) {
            if ($table === 'event_log') {
                continue; // el registro de actividad anota cada petición, también las que no cambian nada
            }
            $counts[$table] = (int) $db->fetchOne('SELECT COUNT(*) FROM `' . $table . '`');
        }

        return $counts;
    }

    public static function tearDownAfterClass(): void
    {
        if (self::updating()) {
            foreach (self::$collected as $module => $profiles) {
                $definition = PermissionMatrix::modules()[$module];
                if (count($profiles) !== count($definition['profiles'])) {
                    continue; // ejecución parcial: no se sobrescribe la instantánea
                }
                file_put_contents(self::SNAPSHOTS . "/$module.json", json_encode(self::snapshot($module, $profiles), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
            }
        }
        parent::tearDownAfterClass();
    }

    /**
     * @param array<string, array<string, bool>> $profiles
     * @return array<string, mixed>
     */
    private static function snapshot(string $module, array $profiles): array
    {
        $definition = PermissionMatrix::modules()[$module];
        $sections = [];
        foreach ($definition['sections'] as $section) {
            $actions = [];
            foreach ($section['actions'] as $action) {
                $allowed = [];
                foreach (array_keys($definition['profiles']) as $profile) {
                    $allowed[$profile] = $profiles[$profile][$action['request']];
                }
                $actions[] = ['label' => $action['label'], 'request' => $action['request'], 'allowed' => $allowed];
            }
            $sections[] = ['title' => $section['title'], 'actions' => $actions];
        }

        return ['module' => $module, 'title' => $definition['title'], 'profiles' => $definition['profiles'], 'sections' => $sections];
    }

    /**
     * @return array<string, bool> petición => permitido, según la instantánea
     */
    private static function expected(string $module, string $profile): array
    {
        $file = self::SNAPSHOTS . "/$module.json";
        self::assertFileExists($file, 'Falta la instantánea; genérala con UPDATE_PERMISSION_MATRIX=1');
        $snapshot = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);

        $expected = [];
        foreach ($snapshot['sections'] as $section) {
            foreach ($section['actions'] as $action) {
                $expected[$action['request']] = $action['allowed'][$profile];
            }
        }

        return $expected;
    }

    private static function word(?bool $allowed): string
    {
        return match ($allowed) {
            true => 'permitido',
            false => 'denegado',
            null => 'sin definir',
        };
    }
}
