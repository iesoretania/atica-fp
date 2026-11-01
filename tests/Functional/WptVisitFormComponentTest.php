<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Formulario dinámico de visitas de FCT (Live Component Wpt:VisitForm). Se prueba como lo usa el navegador: se pide la
 * página, se leen los atributos data-live-* del componente y se hacen las peticiones a /_components/... con los valores
 * modificados. Comprueba la cascada docente → centro de trabajo → acuerdos → alumnado y los permisos en cada petición.
 */
class WptVisitFormComponentTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function teacherId(string $username): int
    {
        return (int) $this->connection()->fetchOne('SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', [$username]);
    }

    /**
     * @return array{url: string, props: string, csrf: string, crawler: Crawler}
     */
    private function componentFor(string $path): array
    {
        $crawler = $this->client->request('GET', $path);
        self::assertResponseIsSuccessful();
        $root = $crawler->filter('[data-live-name-value="Wpt:VisitForm"]');
        self::assertCount(1, $root, 'La página incluye el componente Wpt:VisitForm');

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

    public function testNewVisitPageUsesTheComponentAndTheCascadeOffersTheWorkcentersAgreements(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $teacher = $this->teacherId('alfa.tutor');
        $component = $this->componentFor("/fct/visita/nueva/$teacher");

        $workcenters = $component['crawler']->filter('select[name="visit[workcenter]"] option')->extract(['value']);
        $workcenters = array_values(array_filter($workcenters, static fn (string $v): bool => $v !== ''));
        self::assertNotEmpty($workcenters, 'El docente de las fixtures tiene centros de trabajo');

        // el usuario elige un centro de trabajo: el servidor recalcula los campos que dependen de él
        $this->update($component, ['visit.workcenter' => $workcenters[0]]);

        self::assertResponseIsSuccessful();
        $html = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('visit[agreements][]', $html, 'Aparecen los acuerdos del centro como casillas');
    }

    public function testExistingVisitPageUsesTheComponent(): void
    {
        $this->login($this->getUser('alfa.tutor'));
        $teacher = $this->teacherId('alfa.tutor');
        $id = (int) $this->connection()->fetchOne('SELECT id FROM wpt_contact WHERE teacher_id = ?', [$teacher]);

        $component = $this->componentFor("/fct/visita/$id");

        self::assertCount(1, $component['crawler']->filter('textarea[name="visit[detail]"]'));
    }

    public function testComponentRequestsAreDeniedToAnotherOrganization(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $component = $this->componentFor('/fct/visita/nueva/' . $this->teacherId('alfa.tutor'));

        // la misma petición (con sus propiedades firmadas) hecha por la organización Beta debe rechazarse en el servidor
        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['visit.detail' => 'x']);

        self::assertResponseStatusCodeSame(403);
    }
}
