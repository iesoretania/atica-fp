<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Formulario dinámico de actividades de FFEOE (Live Component Itp:ActivityForm): cascada módulos → resultados de
 * aprendizaje → criterios, selecciones que dejan de ser válidas y permisos en cada petición.
 */
class ItpActivityFormComponentTest extends AppWebTestCase
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
        $root = $crawler->filter('[data-live-name-value="Itp:ActivityForm"]');
        self::assertCount(1, $root, 'La página incluye el componente Itp:ActivityForm');

        return $this->attributes($root) + ['crawler' => $crawler];
    }

    /**
     * @return array{url: string, props: string, csrf: string}
     */
    private function attributes(Crawler $root): array
    {
        return [
            'url' => $root->attr('data-live-url-value'),
            'props' => $root->attr('data-live-props-value'),
            'csrf' => $root->attr('data-live-csrf-value'),
        ];
    }

    private function update(array $component, array $updated): Crawler
    {
        $this->client->request('POST', $component['url'], [
            'data' => json_encode(['props' => json_decode($component['props'], true), 'updated' => $updated]),
        ], [], [
            'HTTP_X-CSRF-TOKEN' => $component['csrf'],
            'HTTP_ACCEPT' => 'application/vnd.live-component+html',
        ]);
        self::assertResponseIsSuccessful();
        $html = new Crawler((string) $this->client->getResponse()->getContent());
        self::assertStringNotContainsString('La opción seleccionada no es válida', $html->text());

        return $html;
    }

    public function testChoosingSubjectsOffersTheirLearningOutcomesAndCriteria(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->componentFor('/formacion/plan/actividad/nueva/' . $this->first('itp_program_grade'));

        $subjects = $component['crawler']->filter('input[name="activity[subjects][]"]')->extract(['value']);
        self::assertNotEmpty($subjects, 'El ciclo de las fixtures tiene módulos profesionales');

        // 1) un módulo marcado ofrece sus resultados de aprendizaje
        $html = $this->update($component, ['activity.subjects' => [$subjects[0]]]);
        $outcomes = $html->filter('input[name="activity[learningOutcomes][]"]')->extract(['value']);
        self::assertNotEmpty($outcomes, 'Aparecen los resultados de aprendizaje del módulo');

        // 2) un resultado marcado ofrece sus criterios
        $next = $this->attributes($html->filter('[data-live-name-value="Itp:ActivityForm"]'));
        $html = $this->update($next, ['activity.learningOutcomes' => [$outcomes[0]]]);
        $criteria = $html->filter('input[name="activity[criteria][]"]')->extract(['value']);
        self::assertNotEmpty($criteria, 'Aparecen los criterios del resultado de aprendizaje');

        // 3) se marca un criterio y después se desmarca el módulo: resultados y criterios desaparecen sin error de validación
        $next = $this->attributes($html->filter('[data-live-name-value="Itp:ActivityForm"]'));
        $html = $this->update($next, ['activity.criteria' => [$criteria[0]]]);
        $next = $this->attributes($html->filter('[data-live-name-value="Itp:ActivityForm"]'));
        $html = $this->update($next, ['activity.subjects' => []]);
        self::assertCount(0, $html->filter('input[name="activity[learningOutcomes][]"]'));
        self::assertCount(0, $html->filter('input[name="activity[criteria][]"]'));
    }

    public function testExistingActivityShowsTheDangerZone(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $id = $this->first('itp_activity');

        $component = $this->componentFor("/formacion/plan/actividad/$id");

        self::assertCount(1, $component['crawler']->filter('input[name="items[]"][value="' . $id . '"]'));
    }

    public function testComponentRequestsAreDeniedToAnotherOrganization(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->componentFor('/formacion/plan/actividad/nueva/' . $this->first('itp_program_grade'));

        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->client->request('POST', $component['url'], [
            'data' => json_encode(['props' => json_decode($component['props'], true), 'updated' => ['activity.name' => 'x']]),
        ], [], ['HTTP_X-CSRF-TOKEN' => $component['csrf'], 'HTTP_ACCEPT' => 'application/vnd.live-component+html']);

        self::assertResponseStatusCodeSame(403);
    }
}
