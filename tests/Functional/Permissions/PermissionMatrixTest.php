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

        return [
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
