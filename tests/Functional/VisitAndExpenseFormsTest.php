<?php

namespace App\Tests\Functional;

use Doctrine\DBAL\Connection;

/**
 * Formularios de alta y edición de visitas, contactos y gastos de desplazamiento de FCT, FP dual y FFE.
 * Los valores válidos se toman del formulario de edición del registro de las fixtures, que pertenece a:
 *  - FCT y FP dual: el tutor de grupo ("tutor"); FFE: el profesor ("profesor")
 */
class VisitAndExpenseFormsTest extends AppWebTestCase
{
    /**
     * @return array<string, array{table: string, form: string, edit: string, new: string, text: string, owner: string, outsider: string, readOnly: ?string}>
     */
    private static function kinds(): array
    {
        return [
            'FCT visita' => [
                'table' => 'wpt_contact', 'form' => 'visit', 'edit' => '/fct/visita/{id}', 'new' => '/fct/visita/nueva/{teacher}',
                'text' => 'detail', 'owner' => 'alfa.tutor', 'outsider' => 'alfa.profesor', 'readOnly' => null,
            ],
            'FCT gasto' => [
                'table' => 'wpt_travel_expense', 'form' => 'travel_expense', 'edit' => '/fct/desplazamiento/detalle/{id}',
                'new' => '/fct/desplazamiento/nuevo/{teacher}', 'text' => 'description', 'owner' => 'alfa.tutor',
                'outsider' => 'alfa.profesor', 'readOnly' => null,
            ],
            'FP dual contacto' => [
                'table' => 'wlt_contact', 'form' => 'contact', 'edit' => '/dual/contacto/{id}', 'new' => '/dual/contacto/nuevo',
                'text' => 'detail', 'owner' => 'alfa.tutor', 'outsider' => 'alfa.profesor', 'readOnly' => 'alfa.coordinador',
            ],
            'FP dual gasto' => [
                'table' => 'wlt_travel_expense', 'form' => 'travel_expense', 'edit' => '/dual/desplazamiento/detalle/{id}',
                'new' => '/dual/desplazamiento/nuevo/{teacher}', 'text' => 'description', 'owner' => 'alfa.tutor',
                'outsider' => 'alfa.profesor', 'readOnly' => null,
            ],
            'FFE contacto' => [
                'table' => 'itp_contact', 'form' => 'contact', 'edit' => '/formacion/contacto/{id}', 'new' => '/formacion/contacto/nuevo',
                'text' => 'detail', 'owner' => 'alfa.profesor', 'outsider' => 'alfa.alumno7', 'readOnly' => 'alfa.tutor',
            ],
            'FFE gasto' => [
                'table' => 'itp_travel_expense', 'form' => 'travel_expense', 'edit' => '/formacion/desplazamiento/detalle/{id}',
                'new' => '/formacion/desplazamiento/nuevo/{teacher}', 'text' => 'description', 'owner' => 'alfa.profesor',
                'outsider' => 'alfa.alumno7', 'readOnly' => null,
            ],
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function kindProvider(): iterable
    {
        foreach (array_keys(self::kinds()) as $kind) {
            yield $kind => [$kind];
        }
    }

    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    /**
     * @return array{id: int, teacher: int, editUrl: string, newUrl: string, config: array<string, mixed>}
     */
    private function context(string $kind): array
    {
        $config = self::kinds()[$kind];
        $id = (int) $this->connection()->fetchOne("SELECT id FROM {$config['table']} ORDER BY id LIMIT 1");
        $teacher = (int) $this->connection()->fetchOne("SELECT teacher_id FROM {$config['table']} WHERE id = $id");

        return [
            'id' => $id,
            'teacher' => $teacher,
            'editUrl' => str_replace('{id}', (string) $id, $config['edit']),
            'newUrl' => str_replace('{teacher}', (string) $teacher, $config['new']),
            'config' => $config,
        ];
    }

    private function rows(string $table): int
    {
        return (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM $table");
    }

    private function text(string $table, int $id, string $field): ?string
    {
        $column = $field === 'detail' ? 'detail' : 'description';

        return $this->connection()->fetchOne("SELECT $column FROM $table WHERE id = $id") ?: null;
    }

    /**
     * Valores válidos del formulario de edición del registro, tal como los enviaría el navegador
     *
     * @return array<string, mixed>
     */
    private function validValues(string $editUrl, string $formName): array
    {
        $crawler = $this->client->request('GET', $editUrl);
        self::assertResponseIsSuccessful($editUrl);
        $form = $crawler->selectButton('Guardar cambios')->form();
        $values = $form->getPhpValues();
        self::assertArrayHasKey($formName, $values);

        return $values;
    }

    /**
     * @dataProvider kindProvider
     */
    public function testOwnerCreatesARecordWithValidData(string $kind): void
    {
        $ctx = $this->context($kind);
        $config = $ctx['config'];
        $this->login($this->getUser($config['owner']));
        $values = $this->validValues($ctx['editUrl'], $config['form']);
        $values[$config['form']][$config['text']] = 'Registro creado desde el formulario';
        $before = $this->rows($config['table']);

        $this->client->request('POST', $ctx['newUrl'], $values);

        self::assertResponseRedirects();
        self::assertSame($before + 1, $this->rows($config['table']));
        $newId = (int) $this->connection()->fetchOne("SELECT MAX(id) FROM {$config['table']}");
        self::assertSame('Registro creado desde el formulario', $this->text($config['table'], $newId, $config['text']));
        self::assertSame($ctx['teacher'], (int) $this->connection()->fetchOne("SELECT teacher_id FROM {$config['table']} WHERE id = $newId"));
    }

    /**
     * @dataProvider kindProvider
     */
    public function testOwnerEditsTheirRecord(string $kind): void
    {
        $ctx = $this->context($kind);
        $config = $ctx['config'];
        $this->login($this->getUser($config['owner']));
        $values = $this->validValues($ctx['editUrl'], $config['form']);
        $values[$config['form']][$config['text']] = 'Texto modificado';

        $this->client->request('POST', $ctx['editUrl'], $values);

        self::assertResponseRedirects();
        self::assertSame('Texto modificado', $this->text($config['table'], $ctx['id'], $config['text']));
    }

    /**
     * @dataProvider kindProvider
     */
    public function testIncompleteDataIsRejectedAndNothingIsCreated(string $kind): void
    {
        $ctx = $this->context($kind);
        $config = $ctx['config'];
        $this->login($this->getUser($config['owner']));
        $values = $this->validValues($ctx['editUrl'], $config['form']);
        // se vacían los campos de selección obligatorios (centro de trabajo, convenios, ruta…)
        foreach (array_keys($values[$config['form']]) as $field) {
            if (!in_array($field, ['_token', $config['text'], 'teacher'], true) && !is_array($values[$config['form']][$field])) {
                $values[$config['form']][$field] = '';
            } elseif (is_array($values[$config['form']][$field] ?? null) && !in_array($field, ['fromDateTime', 'toDateTime', 'dateTime'], true)) {
                unset($values[$config['form']][$field]);
            }
        }
        $before = $this->rows($config['table']);

        $this->client->request('POST', $ctx['newUrl'], $values);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($before, $this->rows($config['table']));
    }

    /**
     * @dataProvider kindProvider
     */
    public function testOutsiderCannotCreateOrEditTheRecordOfAnotherTeacher(string $kind): void
    {
        $ctx = $this->context($kind);
        $config = $ctx['config'];
        $this->login($this->getUser($config['owner']));
        $values = $this->validValues($ctx['editUrl'], $config['form']);
        $values[$config['form']][$config['text']] = 'Texto ajeno';
        $before = $this->rows($config['table']);
        $original = $this->text($config['table'], $ctx['id'], $config['text']);

        $this->login($this->getUser($config['outsider']));
        $this->client->request('POST', $ctx['newUrl'], $values);
        // si el docente va en la URL se deniega; si el alta es siempre del propio usuario, el docente enviado no es
        // una opción válida y el formulario se rechaza sin crear nada (se comprueba más abajo)
        if (str_contains($config['new'], '{teacher}')) {
            self::assertResponseStatusCodeSame(403);
        } else {
            self::assertContains($this->client->getResponse()->getStatusCode(), [200, 403]);
        }
        $this->client->request('POST', $ctx['editUrl'], $values);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->rows($config['table']));
        self::assertSame($original, $this->text($config['table'], $ctx['id'], $config['text']));
    }

    /**
     * @dataProvider kindProvider
     */
    public function testOwnerCannotAssignTheRecordToAnotherTeacher(string $kind): void
    {
        $ctx = $this->context($kind);
        $config = $ctx['config'];
        $anotherTeacher = (int) $this->connection()->fetchOne(
            'SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?',
            [$config['owner'] === 'alfa.profesor' ? 'alfa.tutor' : 'alfa.profesor']
        );
        $this->login($this->getUser($config['owner']));
        $values = $this->validValues($ctx['editUrl'], $config['form']);
        if (!array_key_exists('teacher', $values[$config['form']])) {
            self::markTestSkipped('El formulario no permite elegir el docente');
        }
        $values[$config['form']]['teacher'] = (string) $anotherTeacher;
        $values[$config['form']][$config['text']] = 'Asignado a otro docente';

        $this->client->request('POST', $ctx['editUrl'], $values);

        // el docente no está entre los que puede elegir: el formulario no se acepta y el registro no cambia de dueño
        self::assertSame($ctx['teacher'], (int) $this->connection()->fetchOne("SELECT teacher_id FROM {$config['table']} WHERE id = {$ctx['id']}"));
    }

    /**
     * @dataProvider kindProvider
     */
    public function testUserWithoutManagePermissionCannotChangeTheRecord(string $kind): void
    {
        $ctx = $this->context($kind);
        $config = $ctx['config'];
        if ($config['readOnly'] === null) {
            self::markTestSkipped('Todos los que pueden abrirlo pueden modificarlo');
        }
        $this->login($this->getUser($config['owner']));
        $values = $this->validValues($ctx['editUrl'], $config['form']);
        $values[$config['form']][$config['text']] = 'Cambio sin permiso';
        $original = $this->text($config['table'], $ctx['id'], $config['text']);

        $this->login($this->getUser($config['readOnly']));
        $this->client->request('POST', $ctx['editUrl'], $values);

        self::assertSame($original, $this->text($config['table'], $ctx['id'], $config['text']));
    }
}
