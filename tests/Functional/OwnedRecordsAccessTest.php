<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Visitas, contactos y gastos de desplazamiento de FCT, FP dual y FFE: cada uno pertenece a un docente y solo lo
 * ven o modifican quienes tienen relación con él. Las fixtures tienen uno de cada tipo por módulo:
 *  - FCT y FP dual: del tutor de grupo ("tutor"); FFE: del profesor ("profesor")
 * Los informes en PDF (/{id}/descargar) reciben el identificador del docente, no el del registro.
 */
class OwnedRecordsAccessTest extends AppWebTestCase
{
    private const USERS = ['tutor', 'profesor', 'jefe', 'admin', 'coordinador', 'gestorffe', 'economico', 'tutorlaboral', 'alumno1'];

    /**
     * Para cada acción: método, tabla del registro, plantilla de la URL ({id} registro, {teacher} docente), si
     * envía el registro en "items" y quiénes la pueden realizar
     *
     * @return array<string, array{string, string, string, bool, list<string>}>
     */
    private static function actions(): array
    {
        $all = ['tutor', 'profesor', 'jefe', 'admin', 'gestorffe', 'economico'];

        return [
            'FCT visita: abrir' => ['GET', 'wpt_contact', '/fct/visita/{id}', false, ['tutor', 'jefe', 'admin']],
            'FCT visita: informe' => ['GET', 'wpt_contact', '/fct/visita/{teacher}/descargar', false, ['tutor', 'jefe', 'admin']],
            'FCT visita: borrar' => ['POST', 'wpt_contact', '/fct/visita/eliminar/{teacher}', true, ['tutor', 'jefe', 'admin']],
            'FCT gasto: abrir' => ['GET', 'wpt_travel_expense', '/fct/desplazamiento/detalle/{id}', false, ['tutor', 'jefe', 'admin', 'economico']],
            'FCT gasto: informe' => ['GET', 'wpt_travel_expense', '/fct/desplazamiento/{teacher}/descargar', false, ['tutor', 'jefe', 'admin', 'economico']],
            'FCT gasto: borrar' => ['POST', 'wpt_travel_expense', '/fct/desplazamiento/eliminar/{teacher}', true, ['tutor', 'admin', 'economico']],
            'FP dual contacto: abrir' => ['GET', 'wlt_contact', '/dual/contacto/{id}', false, ['tutor', 'jefe', 'admin', 'coordinador']],
            'FP dual contacto: borrar' => ['POST', 'wlt_contact', '/dual/contacto/eliminar', true, ['tutor', 'admin']],
            'FP dual gasto: abrir' => ['GET', 'wlt_travel_expense', '/dual/desplazamiento/detalle/{id}', false, ['tutor', 'jefe', 'admin', 'economico']],
            'FP dual gasto: informe' => ['GET', 'wlt_travel_expense', '/dual/desplazamiento/{teacher}/descargar', false, ['tutor', 'jefe', 'admin', 'economico']],
            'FP dual gasto: borrar' => ['POST', 'wlt_travel_expense', '/dual/desplazamiento/eliminar/{teacher}', true, ['tutor', 'admin', 'economico']],
            'FFE contacto: abrir' => ['GET', 'itp_contact', '/formacion/contacto/{id}', false, ['tutor', 'profesor', 'jefe', 'admin', 'gestorffe']],
            'FFE contacto: borrar' => ['POST', 'itp_contact', '/formacion/contacto/eliminar', true, ['profesor', 'admin']],
            'FFE gasto: abrir' => ['GET', 'itp_travel_expense', '/formacion/desplazamiento/detalle/{id}', false, $all],
            'FFE gasto: informe' => ['GET', 'itp_travel_expense', '/formacion/desplazamiento/{teacher}/descargar', false, $all],
            'FFE gasto: borrar' => ['POST', 'itp_travel_expense', '/formacion/desplazamiento/eliminar/{teacher}', true, ['profesor', 'jefe', 'admin', 'gestorffe', 'economico']],
        ];
    }

    /**
     * @return iterable<string, array{string, string}> acción y usuario
     */
    public static function combinationProvider(): iterable
    {
        foreach (array_keys(self::actions()) as $action) {
            foreach (self::USERS as $user) {
                yield "$action ($user)" => [$action, $user];
            }
        }
    }

    /**
     * @dataProvider combinationProvider
     */
    public function testAccessMatchesTheRoleOfEachUser(string $action, string $user): void
    {
        [$method, $table, $template, $deletes, $allowed] = self::actions()[$action];
        /** @var Connection $connection */
        $connection = static::getContainer()->get('doctrine')->getConnection();
        $record = (int) $connection->fetchOne("SELECT id FROM $table ORDER BY id LIMIT 1");
        $teacher = (int) $connection->fetchOne("SELECT teacher_id FROM $table WHERE id = $record");
        $url = str_replace(['{id}', '{teacher}'], [(string) $record, (string) $teacher], $template);

        $this->login($this->getUser('alfa.' . $user));
        $this->client->request($method, $url, $deletes ? ['items' => [$record], 'confirm' => 'ok'] : []);
        $status = $this->client->getResponse()->getStatusCode();

        if (in_array($user, $allowed, true)) {
            self::assertLessThan(400, $status, "$user debería poder: $action (HTTP $status)");
            if ($deletes) {
                self::assertSame(0, (int) $connection->fetchOne("SELECT COUNT(*) FROM $table WHERE id = $record"), 'El registro se borra');
            }
        } else {
            self::assertSame(403, $status, "$user no debería poder: $action (HTTP $status)");
            if ($deletes) {
                self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM $table WHERE id = $record"), 'El registro se conserva');
            }
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function actionProvider(): iterable
    {
        foreach (array_keys(self::actions()) as $action) {
            yield $action => [$action];
        }
    }

    /**
     * @dataProvider actionProvider
     */
    public function testAdministratorOfAnotherOrganizationNeverGetsAccess(string $action): void
    {
        [$method, $table, $template, $deletes] = self::actions()[$action];
        /** @var Connection $connection */
        $connection = static::getContainer()->get('doctrine')->getConnection();
        $record = (int) $connection->fetchOne("SELECT id FROM $table ORDER BY id LIMIT 1");
        $teacher = (int) $connection->fetchOne("SELECT teacher_id FROM $table WHERE id = $record");
        $url = str_replace(['{id}', '{teacher}'], [(string) $record, (string) $teacher], $template);

        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->client->request($method, $url, $deletes ? ['items' => [$record], 'confirm' => 'ok'] : []);

        self::assertSame(403, $this->client->getResponse()->getStatusCode(), $action);
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM $table WHERE id = $record"));
    }
}
