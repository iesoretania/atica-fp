<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Listados dinámicos de los datos del centro (Live Components Organization:*List). Para cada uno se hace la petición
 * que haría el navegador al filtrar y al cambiar de página, y se comprueba que otro centro no puede repetirla.
 */
class OrganizationListComponentsTest extends AppWebTestCase
{
    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    /**
     * @return iterable<string, array{string, string}> nombre del componente y ruta de la página (con marcadores)
     */
    public static function listProvider(): iterable
    {
        yield 'tipos de contacto' => ['Organization:ContactMethodList', '/centro/contactos/listar/{year}'];
        yield 'departamentos' => ['Organization:DepartmentList', '/centro/departamento/listar/{year}'];
        yield 'niveles' => ['Organization:GradeList', '/centro/nivel/listar/{year}'];
        yield 'grupos' => ['Organization:GroupList', '/centro/grupo/listar/{year}'];
        yield 'días no lectivos' => ['Organization:NonWorkingDayList', '/centro/dia_no_lectivo/listar/{year}'];
        yield 'enseñanzas' => ['Organization:TrainingList', '/centro/ensenanza/listar/{year}'];
        yield 'módulos' => ['Organization:SubjectList', '/centro/materia/listar/{year}'];
        yield 'profesorado' => ['Organization:TeacherList', '/centro/profesorado/listar/{year}'];
        yield 'matrículas' => ['Organization:StudentEnrollmentList', '/centro/matricula/listar/{year}'];
        yield 'escalas de valoración' => ['Organization:PerformanceScaleList', '/centro/escala/listar/1/'];
        yield 'plantillas de informe' => ['Organization:ReportTemplateList', '/centro/plantilla/listar'];
        yield 'encuestas' => ['Organization:SurveyList', '/centro/encuesta/listar'];
        yield 'cursos académicos' => ['Organization:AcademicYearList', '/centro/cursoacademico/listar'];
        yield 'itinerarios' => ['Organization:TravelRouteList', '/centro/itinerario/listar'];
    }

    private function path(string $template): string
    {
        return strtr($template, ['{year}' => (string) $this->year()]);
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
    public function testFilterSortAndPageChangeWorkThroughTheLiveEndpoint(string $name, string $template): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->component($name, $this->path($template));

        $this->update($component, ['q' => 'zzzz-no-existe']);
        self::assertResponseIsSuccessful("$name: filtro");
        $rows = (new Crawler((string) $this->client->getResponse()->getContent()))->filter('#list tbody tr');
        self::assertCount(1, $rows, "$name: el filtro sin coincidencias solo deja la fila de «no hay resultados»");
        self::assertCount(1, $rows->filter('td[colspan]'), "$name: la única fila es la de «no hay resultados»");

        $this->update($component, ['q' => '', 'page' => 99]);
        self::assertResponseIsSuccessful("$name: página fuera de rango");

        $this->update($component, ['q' => '', 'sort' => 'x); DROP TABLE person; --']);
        self::assertResponseIsSuccessful("$name: columna no ordenable");
    }

    /**
     * @dataProvider listProvider
     */
    public function testAnotherOrganizationCannotReplayTheRequest(string $name, string $template): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $page = $this->client->request('GET', $this->path($template));
        $component = $this->component($name, $this->path($template));
        $idsOfA = $page->filter('input.selectable')->each(static fn (Crawler $c): string => $c->attr('value'));

        // las propiedades están firmadas, pero no ligadas a la sesión: se comprueba que el servidor vuelve a autorizar
        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['q' => '']);

        if (str_contains($template, '{year}')) {
            // el curso académico viaja en las propiedades: es de otro centro
            self::assertResponseStatusCodeSame(403, "$name: otro centro no puede repetir la petición");

            return;
        }

        // los listados del centro no llevan el curso: se calculan siempre con el centro de quien pide, sin datos del otro
        self::assertResponseIsSuccessful($name);
        $idsOfB = (new Crawler((string) $this->client->getResponse()->getContent()))->filter('input.selectable')
            ->each(static fn (Crawler $c): string => $c->attr('value'));
        self::assertSame([], array_intersect($idsOfA, $idsOfB), "$name: aparecen datos de otro centro");
    }
}
