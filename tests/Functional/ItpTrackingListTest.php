<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;

/**
 * Listado de seguimiento de la FFE y su exportación a CSV: qué estancias ve cada rol y cómo se escriben los datos.
 *  - alumno7: tutor docente "profesor", tutor laboral "tutorlaboral"
 *  - alumno8: tutor docente "tutor", tutor laboral "tutorlaboral2"
 * El gestor de la FFE ("gestorffe") gestiona el grupo de ambos.
 */
class ItpTrackingListTest extends AppWebTestCase
{
    private function year(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->getOrganization($code)->getCurrentAcademicYear()->getId();
    }

    /**
     * @return list<list<string>> filas de datos del CSV (sin la cabecera)
     */
    private function csvRows(string $username, string $query = ''): array
    {
        $this->login($this->getUser($username));
        $this->client->request('GET', '/formacion/seguimiento/exportar/' . $this->year(), $query === '' ? [] : ['q' => $query]);
        self::assertResponseIsSuccessful();

        $content = $this->client->getInternalResponse()->getContent();
        self::assertStringStartsWith("\xEF\xBB\xBF", $content, 'El CSV lleva BOM UTF-8');
        $lines = array_values(array_filter(explode("\n", substr($content, 3)), static fn(string $line): bool => $line !== ''));
        array_shift($lines);

        return array_map(static fn(string $line): array => str_getcsv($line, ';', '"', ''), $lines);
    }

    /**
     * @return iterable<string, array{string, int}>
     */
    public static function visibilityProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin', 2];
        yield 'gestor de la FFE' => ['alfa.gestorffe', 2];
        yield 'jefe de departamento' => ['alfa.jefe', 2];
        yield 'tutor docente de alumno7' => ['alfa.profesor', 1];
        // además de tutor docente de alumno8 es tutor del grupo, por lo que ve las estancias de todo el grupo
        yield 'tutor docente y de grupo' => ['alfa.tutor', 2];
        yield 'tutor laboral de alumno7' => ['alfa.tutorlaboral', 1];
        yield 'tutor laboral de alumno8' => ['alfa.tutorlaboral2', 1];
        yield 'alumno7' => ['alfa.alumno7', 1];
        yield 'alumno8' => ['alfa.alumno8', 1];
    }

    /**
     * @dataProvider visibilityProvider
     */
    public function testEachRoleSeesAndExportsOnlyItsStays(string $username, int $expected): void
    {
        $rows = $this->csvRows($username);

        self::assertCount($expected, $rows, $username);
        foreach ($rows as $row) {
            self::assertCount(11, $row, 'Cada fila tiene todas las columnas');
        }
    }

    public function testListShowsTheSameStaysAsTheExport(): void
    {
        $this->login($this->getUser('alfa.alumno8'));

        $this->client->request('GET', '/formacion/seguimiento/listar/' . $this->year());

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Alumno 8', $content);
        self::assertStringNotContainsString('Alumno 7', $content);
    }

    public function testSearchFiltersTheListAndTheExport(): void
    {
        $rows = $this->csvRows('alfa.admin', 'Alumno 7');

        self::assertCount(1, $rows);
        self::assertStringContainsString('Alumno 7', $rows[0][0]);
    }

    public function testExportHasHoursInHoursAndAttachmentHeaders(): void
    {
        $rows = $this->csvRows('alfa.gestorffe');

        // las horas del CSV están en horas (con coma decimal), no en centésimas
        foreach ($rows as $row) {
            self::assertMatchesRegularExpression('/^\d+,\d{2}$/', $row[9]);
            self::assertMatchesRegularExpression('/^\d+,\d{2}$/', $row[10]);
            self::assertLessThan(5000, (float) str_replace(',', '.', $row[9]));
        }
        self::assertStringContainsString('attachment', (string) $this->client->getResponse()->headers->get('Content-Disposition'));
        self::assertStringStartsWith('text/csv', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function formulaProvider(): iterable
    {
        yield 'igual' => ['=HYPERLINK("http://example.com")'];
        yield 'suma' => ['+SUM(A1:A9)'];
        yield 'resta' => ['-2+3'];
        yield 'arroba' => ['@SUM(1)'];
    }

    /**
     * @dataProvider formulaProvider
     */
    public function testExportNeutralizesSpreadsheetFormulas(string $name): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $student = $this->getUser('alfa.alumno7');
        $student->setFirstName($name);
        $em->flush();
        $em->clear();

        $rows = $this->csvRows('alfa.admin', 'Alumno 8');
        self::assertCount(1, $rows);

        $content = $this->csvAll();
        // ninguna celda empieza por un carácter que una hoja de cálculo ejecutaría
        foreach (str_getcsv($content, ';', '"', '') as $cell) {
            self::assertDoesNotMatchRegularExpression('/^[=+@]/', trim($cell), 'Fórmula sin neutralizar: ' . $cell);
        }
        self::assertStringContainsString("'" . str_replace('"', '', $name), str_replace('"', '', $content));
    }

    /**
     * Contenido completo del CSV sin cabecera, con todas las estancias (para ver el alumno modificado)
     */
    private function csvAll(): string
    {
        $this->login($this->getUser('alfa.admin'));
        $this->client->request('GET', '/formacion/seguimiento/exportar/' . $this->year());
        $lines = explode("\n", substr($this->client->getInternalResponse()->getContent(), 3));
        array_shift($lines);

        return implode(';', array_filter($lines, static fn(string $line): bool => $line !== ''));
    }

    // --- Permisos ----------------------------------------------------------------------------------

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        yield 'alumno de FCT' => ['alfa.alumno1'];
        yield 'alumno de FP dual' => ['alfa.alumno5'];
        yield 'responsable económico' => ['alfa.economico'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testPeopleOutsideTheModuleSeeNoStays(string $username): void
    {
        $this->login($this->getUser($username));

        $this->client->request('GET', '/formacion/seguimiento/exportar/' . $this->year());
        $status = $this->client->getResponse()->getStatusCode();

        if ($status === 200) {
            self::assertCount(0, $this->csvRows($username));
        } else {
            self::assertSame(403, $status);
        }
    }

    public function testAdministratorOfAnotherOrganizationCannotUseAnotherOrganizationsYear(): void
    {
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        $this->client->request('GET', '/formacion/seguimiento/exportar/' . $this->year());
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/formacion/seguimiento/listar/' . $this->year());
        self::assertResponseStatusCodeSame(403);
    }

    public function testAnonymousUsersAreSentToTheLogin(): void
    {
        $this->client->request('GET', '/formacion/seguimiento/exportar/' . $this->year());

        self::assertResponseRedirects();
    }
}
