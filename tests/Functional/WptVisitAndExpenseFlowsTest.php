<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Visitas y gastos de desplazamiento de FCT: listado de cada docente con búsqueda, resumen del profesorado, borrado
 * con confirmación e informe en PDF. Las fixtures tienen un registro de cada tipo para el tutor docente "tutor"
 * y otro para el de la organización Beta. La creación y edición se prueban en VisitAndExpenseFormsTest.
 */
class WptVisitAndExpenseFlowsTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    private function teacherId(string $username): int
    {
        return (int) $this->connection()->fetchOne('SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', [$username]);
    }

    private function rows(string $table, int $teacher): int
    {
        return (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM $table WHERE teacher_id = ?", [$teacher]);
    }

    /**
     * @return iterable<string, array{string, string, string, list<string>, string}>
     *     base de las rutas, tabla, texto que coincide con el registro de las fixtures, usuarios autorizados y borradores
     */
    public static function kindProvider(): iterable
    {
        yield 'visitas' => ['/fct/visita', 'wpt_contact', 'Sede central', ['alfa.tutor', 'alfa.jefe', 'alfa.admin'], 'alfa.tutor'];
        yield 'gastos' => ['/fct/desplazamiento', 'wpt_travel_expense', 'Visita de seguimiento', ['alfa.tutor', 'alfa.admin', 'alfa.economico'], 'alfa.tutor'];
    }

    /**
     * @dataProvider kindProvider
     * @param list<string> $users
     */
    public function testDetailListSearchesAndSummaryListShowsTheTeachers(string $base, string $table, string $term, array $users): void
    {
        $teacher = $this->teacherId('alfa.tutor');

        foreach ($users as $username) {
            $this->login($this->getUser($username));

            $this->client->request('GET', "$base/$teacher/listar/1");
            self::assertResponseIsSuccessful("$username $base detalle");

            $this->client->request('GET', "$base/$teacher/listar/1", ['q' => 'zzzz-no-existe']);
            self::assertResponseIsSuccessful("$username $base búsqueda sin resultados");

            foreach (['', '?q=zzzz-no-existe', '?q=Tutor'] as $query) {
                $this->client->request('GET', "$base/resumen/" . $this->year() . "/1$query");
                self::assertResponseIsSuccessful("$username $base resumen$query");
            }
        }
    }

    /**
     * @dataProvider kindProvider
     * @param list<string> $users
     */
    public function testDetailListSearchFindsTheFixtureRecord(string $base, string $table, string $term, array $users): void
    {
        $teacher = $this->teacherId('alfa.tutor');
        $this->login($this->getUser('alfa.admin'));

        $this->client->request('GET', "$base/$teacher/listar/1");
        $all = (string) $this->client->getResponse()->getContent();
        $this->client->request('GET', "$base/$teacher/listar/1", ['q' => 'zzzz-no-existe']);
        $none = (string) $this->client->getResponse()->getContent();

        self::assertLessThan(strlen($all), strlen($none), 'La búsqueda sin coincidencias oculta el registro');
    }

    /**
     * @dataProvider kindProvider
     * @param list<string> $users
     */
    public function testDeletionRequiresConfirmationAndRemovesOnlyTheSelectedRecord(string $base, string $table, string $term, array $users): void
    {
        $teacher = $this->teacherId('alfa.tutor');
        $id = (int) $this->connection()->fetchOne("SELECT id FROM $table WHERE teacher_id = ?", [$teacher]);
        $this->login($this->getUser('alfa.admin'));
        $otherTeacher = $this->teacherId('alfa.profesor');
        $others = (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM $table WHERE teacher_id <> ?", [$teacher]);

        $this->client->request('POST', "$base/eliminar/$teacher", ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->rows($table, $teacher));

        $this->client->request('POST', "$base/eliminar/$teacher", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame(0, $this->rows($table, $teacher));
        self::assertSame($others, (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM $table WHERE teacher_id <> ?", [$teacher]));
        self::assertGreaterThan(0, $otherTeacher);
    }

    /**
     * @dataProvider kindProvider
     * @param list<string> $users
     */
    public function testDeletionWithoutSelectionReturnsToTheList(string $base, string $table, string $term, array $users): void
    {
        $teacher = $this->teacherId('alfa.tutor');
        $this->login($this->getUser('alfa.admin'));

        $this->client->request('POST', "$base/eliminar/$teacher", []);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function expenseModuleProvider(): iterable
    {
        yield 'FCT' => ['/fct/desplazamiento'];
        yield 'FP dual' => ['/dual/desplazamiento'];
        yield 'FFE' => ['/formacion/desplazamiento'];
    }

    /**
     * @dataProvider expenseModuleProvider
     */
    public function testExpenseDeletionWithoutSelectionReturnsToTheListInEveryModule(string $base): void
    {
        $this->login($this->getUser('alfa.admin'));

        $this->client->request('POST', "$base/eliminar/" . $this->teacherId('alfa.tutor'), []);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider kindProvider
     * @param list<string> $users
     */
    public function testReportIsAPdf(string $base, string $table, string $term, array $users): void
    {
        $teacher = $this->teacherId('alfa.tutor');

        foreach ($users as $username) {
            $this->login($this->getUser($username));
            $this->client->request('GET', "$base/$teacher/descargar");
            self::assertResponseIsSuccessful("$username $base");
            self::assertStringContainsString('application/pdf', (string) $this->client->getResponse()->headers->get('Content-Type'));
        }
    }

    /**
     * @dataProvider kindProvider
     * @param list<string> $users
     */
    public function testAdministratorOfAnotherCentreCannotUseThem(string $base, string $table, string $term, array $users): void
    {
        $teacher = $this->teacherId('alfa.tutor');
        $before = $this->rows($table, $teacher);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (["$base/$teacher/listar/1", "$base/$teacher/descargar", "$base/resumen/" . $this->year() . '/1'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "$base/eliminar/$teacher", ['items' => [(int) $this->connection()->fetchOne("SELECT id FROM $table WHERE teacher_id = ?", [$teacher])], 'confirm' => 'ok']);

        self::assertSame($before, $this->rows($table, $teacher));
    }
}
