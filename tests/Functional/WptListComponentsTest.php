<?php

namespace App\Tests\Functional;

use App\Entity\WptModule\Shift;
use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Listados dinámicos de FCT (Live Components Wpt:*List). Para cada uno se pide la página, se leen los atributos
 * data-live-* y se hace la petición que haría el navegador al filtrar y al cambiar de página, y se comprueba
 * que otro centro no puede repetir esas peticiones.
 */
class WptListComponentsTest extends AppWebTestCase
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

    private function shiftId(): int
    {
        return static::getContainer()->get('doctrine')->getManager()->getRepository(Shift::class)->createQueryBuilder('s')
            ->join('s.subject', 'su')->join('su.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization())
            ->setMaxResults(1)->getQuery()->getSingleResult()->getId();
    }

    /**
     * @return array<string, array{string, string}> nombre del componente y ruta de la página (con marcadores)
     */
    public static function listProvider(): iterable
    {
        yield 'convocatorias' => ['Wpt:ShiftList', '/fct/convocatoria/listar/{year}'];
        yield 'programa formativo' => ['Wpt:ActivityList', '/fct/actividad/{shift}/listar'];
        yield 'acuerdos' => ['Wpt:AgreementList', '/fct/acuerdo/{shift}/listar'];
        yield 'visitas' => ['Wpt:VisitList', '/fct/visita/{teacher}/listar'];
        yield 'resumen de visitas' => ['Wpt:VisitTeacherList', '/fct/visita/resumen/{year}'];
        yield 'desplazamientos' => ['Wpt:TravelExpenseList', '/fct/desplazamiento/{teacher}/listar'];
        yield 'resumen de desplazamientos' => ['Wpt:TravelExpenseTeacherList', '/fct/desplazamiento/resumen/{year}'];
        yield 'seguimiento' => ['Wpt:TrackingList', '/fct/seguimiento/acuerdo/listar/{year}'];
        yield 'informe final' => ['Wpt:FinalReportList', '/fct/informe/acuerdo/{year}'];
        yield 'informe de encuestas del alumnado' => ['Wpt:ReportList', '/fct/informe/encuesta/estudiantes/listar/{year}'];
        yield 'informe de encuestas de empresas' => ['Wpt:ReportList', '/fct/informe/encuesta/empresas/listar/{year}'];
        yield 'informe de encuestas del centro' => ['Wpt:ReportList', '/fct/informe/encuesta/centro/listar/{year}'];
        yield 'encuestas del alumnado' => ['Wpt:SurveyStudentList', '/fct/encuesta/estudiante/{year}'];
        yield 'encuestas de empresas' => ['Wpt:SurveyWorkTutorList', '/fct/encuesta/empresa/{year}'];
        yield 'encuestas del centro' => ['Wpt:SurveyEducationalTutorList', '/fct/encuesta/centro/{year}'];
    }

    private function path(string $template): string
    {
        return strtr($template, [
            '{year}' => (string) $this->year(),
            '{shift}' => (string) $this->shiftId(),
            '{teacher}' => (string) $this->teacherId('alfa.tutor'),
        ]);
    }

    /**
     * @return array{url: string, props: string, csrf: string}
     */
    private function component(string $name, string $path): array
    {
        $crawler = $this->client->request('GET', $path);
        self::assertResponseIsSuccessful("GET $path");
        $root = $crawler->filter('[data-live-name-value="' . $name . '"]');
        self::assertCount(1, $root, "La página $path incluye el componente $name");

        return [
            'url' => $root->attr('data-live-url-value'),
            'props' => $root->attr('data-live-props-value'),
            'csrf' => $root->attr('data-live-csrf-value'),
        ];
    }

    private function update(array $component, array $updated): void
    {
        $this->client->request('POST', $component['url'], [
            'data' => json_encode(['props' => json_decode($component['props'], true), 'updated' => $updated]),
        ], [], [
            'HTTP_X-CSRF-TOKEN' => $component['csrf'],
            'HTTP_ACCEPT' => 'application/vnd.live-component+html',
        ]);
    }

    /**
     * @dataProvider listProvider
     */
    public function testFilterAndPageChangeWorkThroughTheLiveEndpoint(string $name, string $template): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->component($name, $this->path($template));

        $this->update($component, ['q' => 'zzzz-no-existe']);
        self::assertResponseIsSuccessful("$name: filtro");
        self::assertStringContainsString('id="search"', (string) $this->client->getResponse()->getContent());
        $rows = (new Crawler((string) $this->client->getResponse()->getContent()))->filter('#list tbody tr');
        self::assertCount(1, $rows, "$name: el filtro sin coincidencias solo deja la fila de «no hay resultados»");
        self::assertCount(1, $rows->filter('td[colspan]'), "$name: la única fila es la de «no hay resultados»");

        $this->update($component, ['q' => '', 'page' => 99]);
        self::assertResponseIsSuccessful("$name: página fuera de rango");
    }

    /**
     * @return list<string> texto de la primera columna de cada fila de la tabla
     */
    private function firstColumn(): array
    {
        $crawler = new Crawler((string) $this->client->getResponse()->getContent());

        return $crawler->filter('#list tbody tr td:first-child')->each(static fn (Crawler $td): string => trim(preg_replace('/\s+/', ' ', $td->text())));
    }

    public function testTrackingListSortsAndFiltersWithOnlyValidValues(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->component('Wpt:TrackingList', $this->path('/fct/seguimiento/acuerdo/listar/{year}'));

        $this->update($component, []);
        $unfiltered = $this->firstColumn();
        self::assertGreaterThan(1, count($unfiltered), 'Las fixtures tienen varios acuerdos');

        // orden por estudiante, ascendente y descendente
        $this->update($component, ['sort' => 'student', 'dir' => 'asc']);
        $ascending = $this->firstColumn();
        $this->update($component, ['sort' => 'student', 'dir' => 'desc']);
        self::assertSame(array_reverse($ascending), $this->firstColumn(), 'El sentido descendente invierte el orden');

        // una columna que no es ordenable se ignora (no llega a la consulta)
        $this->update($component, ['sort' => 'p.lastName); DROP TABLE person; --']);
        self::assertResponseIsSuccessful();
        self::assertSame($unfiltered, $this->firstColumn());

        // un valor de filtro que no es una opción del listado se ignora
        $this->update($component, ['filters' => ['shift' => '999999', 'group' => "1' OR '1'='1"]]);
        self::assertResponseIsSuccessful();
        self::assertSame($unfiltered, $this->firstColumn());

        // un valor válido filtra: aparece el chip y el filtro «Quitar filtros»
        $crawler = new Crawler((string) $this->client->getResponse()->getContent());
        $groups = array_values(array_filter($crawler->filter('select[data-model="filters.group"] option')->extract(['value']), static fn (string $v): bool => $v !== ''));
        self::assertNotEmpty($groups);
        $this->update($component, ['filters' => ['group' => $groups[0]]]);
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('data-live-action-param="clearFilter"', (string) $this->client->getResponse()->getContent());
        self::assertNotEmpty($this->firstColumn());
    }

    /**
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function filteredListProvider(): iterable
    {
        yield 'visitas por fechas' => ['Wpt:VisitList', '/fct/visita/{teacher}/listar', ['from' => '2020-01-01', 'to' => '2099-12-31']];
        yield 'desplazamientos por fechas y verificación' => ['Wpt:TravelExpenseList', '/fct/desplazamiento/{teacher}/listar', ['from' => '2020-01-01', 'to' => '2099-12-31', 'verified' => '0']];
        yield 'convocatorias bloqueadas' => ['Wpt:ShiftList', '/fct/convocatoria/listar/{year}', ['locked' => '0']];
        yield 'acuerdos por centro de trabajo' => ['Wpt:AgreementList', '/fct/acuerdo/{shift}/listar', ['workcenter' => '1']];
    }

    /**
     * @dataProvider filteredListProvider
     * @param array<string, string> $filters
     */
    public function testCustomFiltersAcceptValidValuesAndIgnoreInvalidOnes(string $name, string $template, array $filters): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->component($name, $this->path($template));

        $this->update($component, ['filters' => $filters]);
        self::assertResponseIsSuccessful("$name: filtros válidos");

        // fechas que no lo son y opciones inexistentes se ignoran sin error
        $garbage = array_map(static fn (string $v): string => "'; DROP TABLE person; --", $filters);
        $this->update($component, ['filters' => $garbage]);
        self::assertResponseIsSuccessful("$name: filtros inválidos");
    }

    /**
     * @dataProvider listProvider
     */
    public function testAnotherOrganizationCannotReplayTheRequest(string $name, string $template): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->component($name, $this->path($template));

        // las propiedades están firmadas, pero no ligadas a la sesión: se comprueba que el servidor vuelve a autorizar
        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['q' => 'x']);

        self::assertResponseStatusCodeSame(403, "$name: otro centro no puede repetir la petición");
    }
}
