<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Formulario dinámico de reuniones de FP dual (Live Component Wlt:MeetingForm). Se prueba como lo usa el navegador: se pide
 * la página, se leen los atributos data-live-* del componente y se hacen las peticiones a /_components/... con los valores
 * modificados. Comprueba la cascada autor → proyecto → alumnado y docentes y los permisos en cada petición.
 */
class WltMeetingFormComponentTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    /**
     * @return array{url: string, props: string, csrf: string, crawler: Crawler}
     */
    private function componentFor(string $path): array
    {
        $crawler = $this->client->request('GET', $path);
        self::assertResponseIsSuccessful();
        $root = $crawler->filter('[data-live-name-value="Wlt:MeetingForm"]');
        self::assertCount(1, $root, 'La página incluye el componente Wlt:MeetingForm');

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

    private function update(array $component, array $updated): void
    {
        $this->client->request('POST', $component['url'], [
            'data' => json_encode(['props' => json_decode($component['props'], true), 'updated' => $updated]),
        ], [], [
            'HTTP_X-CSRF-TOKEN' => $component['csrf'],
            'HTTP_ACCEPT' => 'application/vnd.live-component+html',
        ]);
    }

    private function newMeetingUrl(): string
    {
        return '/dual/reunion/nueva/' . $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    public function testNewMeetingPageUsesTheComponentAndTheProjectOffersStudentsAndTeachers(): void
    {
        $this->login($this->getUser('alfa.tutor'));
        $component = $this->componentFor($this->newMeetingUrl());

        $projects = array_values(array_filter(
            $component['crawler']->filter('select[name="meeting[project]"] option')->extract(['value']),
            static fn (string $v): bool => $v !== ''
        ));
        self::assertNotEmpty($projects, 'El docente tiene algún proyecto');

        $this->update($component, ['meeting.project' => $projects[0]]);
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('meeting[teachers][]', $content, 'Al elegir proyecto se ofrecen sus docentes');

        // al quitar el proyecto, alumnado y docentes se vacían sin error de validación
        $next = $this->attributes((new Crawler($content))->filter('[data-live-name-value="Wlt:MeetingForm"]'));
        $this->update($next, ['meeting.project' => '']);
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('La opción seleccionada no es válida', (string) $this->client->getResponse()->getContent());
    }

    public function testExistingMeetingPageUsesTheComponentAndShowsTheDangerZone(): void
    {
        $this->login($this->getUser('alfa.tutor'));
        $id = (int) $this->connection()->fetchOne('SELECT id FROM wlt_meeting ORDER BY id LIMIT 1');
        self::assertGreaterThan(0, $id, 'Las fixtures tienen reuniones de FP dual');

        $component = $this->componentFor("/dual/reunion/$id");

        self::assertCount(1, $component['crawler']->filter('textarea[name="meeting[detail]"]'));
        self::assertCount(1, $component['crawler']->filter('input[name="items[]"][value="' . $id . '"]'), 'La zona de peligro envía la reunión a la operación de borrado');
    }

    public function testExistingMeetingComponentRequestsAreDeniedToAnotherOrganization(): void
    {
        $this->login($this->getUser('alfa.tutor'));
        $id = (int) $this->connection()->fetchOne('SELECT id FROM wlt_meeting ORDER BY id LIMIT 1');
        $component = $this->componentFor("/dual/reunion/$id");

        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['meeting.detail' => 'x']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testComponentRequestsAreDeniedToAnotherOrganization(): void
    {
        $this->login($this->getUser('alfa.profesor'));
        $component = $this->componentFor($this->newMeetingUrl());

        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['meeting.detail' => 'x']);

        self::assertResponseStatusCodeSame(403);
    }
}
