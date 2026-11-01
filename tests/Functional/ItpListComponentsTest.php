<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Listados dinámicos de FFEOE (Live Components Itp:*List). Para cada uno se pide la página, se leen los atributos
 * data-live-* y se hace la petición que haría el navegador al filtrar y al cambiar de página, y se comprueba
 * que otro centro no puede repetir esas peticiones.
 */
class ItpListComponentsTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    private function first(string $table): int
    {
        return (int) $this->connection()->fetchOne("SELECT id FROM $table ORDER BY id LIMIT 1");
    }

    private function teacherId(string $username): int
    {
        return (int) $this->connection()->fetchOne('SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', [$username]);
    }

    /**
     * @return array<string, array{string, string}> nombre del componente y ruta de la página (con marcadores)
     */
    public static function listProvider(): iterable
    {
        yield 'programas formativos' => ['Itp:TrainingProgramList', '/formacion/plan/listar/{year}'];
        yield 'actividades' => ['Itp:ActivityList', '/formacion/plan/actividad/listar/{programGrade}'];
        yield 'empresas' => ['Itp:CompanyList', '/formacion/plan/empresa/listar/{programGrade}'];
        yield 'estudiantes' => ['Itp:StudentProgramList', '/formacion/plan/curso/grupo/estudiante/listar/{programGroup}'];
        yield 'estancias del estudiante' => ['Itp:StudentProgramWorkcenterList', '/formacion/plan/curso/grupo/estudiante/estancia/listar/{studentProgram}'];
        yield 'gestión de estancias' => ['Itp:StudentProgramWorkcenterManageList', '/formacion/plan/curso/grupo/gestion/listar/{programGrade}'];
        yield 'contactos' => ['Itp:ContactList', '/formacion/contacto/listar/{year}'];
        yield 'desplazamientos' => ['Itp:TravelExpenseList', '/formacion/desplazamiento/{teacher}/listar'];
        yield 'resumen de desplazamientos' => ['Itp:TravelExpenseTeacherList', '/formacion/desplazamiento/resumen/{year}'];
        yield 'seguimiento' => ['Itp:TrackingList', '/formacion/seguimiento/listar/{year}'];
        yield 'valoración' => ['Itp:GradingList', '/formacion/valoracion/listar/{year}'];
        yield 'valoración de un grupo' => ['Itp:GradingList', '/formacion/valoracion/evaluacion/grupo/{programGroup}'];
        yield 'grupos a evaluar' => ['Itp:GradingGroupList', '/formacion/valoracion/evaluacion/{year}'];
        yield 'encuestas del alumnado' => ['Itp:SurveyStudentList', '/formacion/encuesta/estudiante/{year}'];
        yield 'encuestas de empresas' => ['Itp:SurveyWorkTutorList', '/formacion/encuesta/empresa/{year}'];
        yield 'encuestas del centro' => ['Itp:SurveyEducationalTutorList', '/formacion/encuesta/centro/{year}'];
        yield 'informe de encuestas del alumnado' => ['Itp:ReportList', '/formacion/informe/encuesta/estudiantes/listar/{year}'];
        yield 'informe de encuestas de empresas' => ['Itp:ReportList', '/formacion/informe/encuesta/empresas/listar/{year}'];
        yield 'informe de encuestas del centro' => ['Itp:ReportList', '/formacion/informe/encuesta/centro/listar/{year}'];
    }

    private function path(string $template): string
    {
        return strtr($template, [
            '{year}' => (string) $this->year(),
            '{programGrade}' => (string) $this->first('itp_program_grade'),
            '{programGroup}' => (string) $this->first('itp_program_group'),
            '{studentProgram}' => (string) $this->first('itp_student_program'),
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
     * @return iterable<string, array{string, string, array<string, string>}>
     */
    public static function filteredListProvider(): iterable
    {
        yield 'contactos por forma y fechas' => ['Itp:ContactList', '/formacion/contacto/listar/{year}', ['method' => '0', 'from' => '2020-01-01', 'to' => '2099-12-31']];
        yield 'desplazamientos por fechas y verificación' => ['Itp:TravelExpenseList', '/formacion/desplazamiento/{teacher}/listar', ['from' => '2020-01-01', 'to' => '2099-12-31', 'verified' => '0']];
        yield 'programas bloqueados y modalidad' => ['Itp:TrainingProgramList', '/formacion/plan/listar/{year}', ['locked' => '0', 'modality' => '1']];
        yield 'estudiantes con estancia' => ['Itp:StudentProgramList', '/formacion/plan/curso/grupo/estudiante/listar/{programGroup}', ['workcenter' => 'with']];
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

    public function testSortingIgnoresColumnsThatAreNotSortable(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->component('Itp:TrackingList', $this->path('/formacion/seguimiento/listar/{year}'));

        $this->update($component, ['sort' => 'student', 'dir' => 'desc']);
        self::assertResponseIsSuccessful();
        $this->update($component, ['sort' => 'p.lastName); DROP TABLE person; --']);
        self::assertResponseIsSuccessful();
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
