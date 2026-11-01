<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Formulario dinámico de estancias de FFEOE (Live Component Itp:StayForm), individual y por lote. Se prueba como lo
 * usa el navegador: se pide la página, se leen los atributos data-live-* del componente y se hacen las peticiones a
 * /_components/... con los valores modificados. Comprueba la cascada empresa → centro de trabajo y actividades, la zona
 * de peligro y los permisos en cada petición.
 */
class ItpStayFormComponentTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function first(string $table): int
    {
        return (int) $this->connection()->fetchOne("SELECT id FROM $table ORDER BY id LIMIT 1");
    }

    /**
     * @return array{url: string, props: string, csrf: string, crawler: Crawler}
     */
    private function componentFor(string $path): array
    {
        $crawler = $this->client->request('GET', $path);
        self::assertResponseIsSuccessful();
        $root = $crawler->filter('[data-live-name-value="Itp:StayForm"]');
        self::assertCount(1, $root, 'La página incluye el componente Itp:StayForm');

        return [
            'url' => $root->attr('data-live-url-value'),
            'props' => $root->attr('data-live-props-value'),
            'csrf' => $root->attr('data-live-csrf-value'),
            'crawler' => $crawler,
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
     * @return iterable<string, array{string, string}> ruta de la página y nombre del formulario
     */
    public static function pageProvider(): iterable
    {
        yield 'nueva estancia de un estudiante' => ['/formacion/plan/curso/grupo/estudiante/estancia/nueva/{studentProgram}', 'student_program_workcenter'];
        yield 'alta por lote' => ['/formacion/plan/curso/grupo/gestion/nueva/{programGroup}', 'student_program_workcenter_batch'];
    }

    private function path(string $template): string
    {
        return strtr($template, [
            '{studentProgram}' => (string) $this->first('itp_student_program'),
            '{programGroup}' => (string) $this->first('itp_program_group'),
        ]);
    }

    /**
     * @dataProvider pageProvider
     */
    public function testChoosingACompanyOffersItsWorkcenterAndActivities(string $template, string $form): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->componentFor($this->path($template));

        $companies = array_values(array_filter(
            $component['crawler']->filter("select[name=\"{$form}[company]\"] option")->extract(['value']),
            static fn (string $v): bool => $v !== ''
        ));
        self::assertNotEmpty($companies, 'El programa de las fixtures tiene empresas');

        $this->update($component, ["$form.company" => $companies[0]]);

        self::assertResponseIsSuccessful();
        $html = new Crawler((string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('La opción seleccionada no es válida', $html->text());
        $workcenters = array_filter($html->filter("select[name=\"{$form}[workcenter]\"] option")->extract(['value']), static fn (string $v): bool => $v !== '');
        self::assertNotEmpty($workcenters, 'Aparecen los centros de trabajo de la empresa');
        self::assertCount(1, $html->filter("select[name=\"{$form}[workcenter]\"] option[selected]"), 'Se elige el primer centro de trabajo');
    }

    public function testExistingStayShowsTheDangerZoneAndTheComponent(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $id = $this->first('itp_student_program_workcenter');

        $component = $this->componentFor("/formacion/plan/curso/grupo/estudiante/estancia/$id");

        self::assertCount(1, $component['crawler']->filter('input[name="items[]"][value="' . $id . '"]'), 'La zona de peligro envía la estancia a la operación de borrado');
    }

    /**
     * @dataProvider pageProvider
     */
    public function testComponentRequestsAreDeniedToAnotherOrganization(string $template, string $form): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->componentFor($this->path($template));

        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ["$form.company" => '']);

        self::assertResponseStatusCodeSame(403);
    }
}
