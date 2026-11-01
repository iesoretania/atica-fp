<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Formulario dinámico de contactos de FFEOE (Live Component Itp:ContactForm). Se prueba como lo usa el navegador: se pide
 * la página, se leen los atributos data-live-* del componente y se hacen las peticiones a /_components/... con los valores
 * modificados. Comprueba la cascada centro de trabajo → planes de formación → alumnado y los permisos en cada petición.
 */
class ItpContactFormComponentTest extends AppWebTestCase
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
        $root = $crawler->filter('[data-live-name-value="Itp:ContactForm"]');
        self::assertCount(1, $root, 'La página incluye el componente Itp:ContactForm');

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

    public function testNewContactPageUsesTheComponentAndTheCascadeOffersTrainingPrograms(): void
    {
        $this->login($this->getUser('alfa.profesor'));
        $component = $this->componentFor('/formacion/contacto/nuevo');

        $workcenters = array_values(array_filter(
            $component['crawler']->filter('select[name="contact[workcenter]"] option')->extract(['value']),
            static fn (string $v): bool => $v !== ''
        ));
        self::assertNotEmpty($workcenters);

        $found = false;
        foreach ($workcenters as $workcenter) {
            $this->update($component, ['contact.workcenter' => $workcenter]);
            self::assertResponseIsSuccessful();
            if (str_contains((string) $this->client->getResponse()->getContent(), 'contact[trainingPrograms][]')) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, 'Algún centro de trabajo de las fixtures ofrece planes de formación como casillas');

        // al volver a elegir otro centro, los planes elegidos se vacían sin error de validación
        $crawler = new Crawler((string) $this->client->getResponse()->getContent());
        $next = $this->attributes($crawler->filter('[data-live-name-value="Itp:ContactForm"]'));
        $this->update($next, ['contact.workcenter' => '']);
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('La opción seleccionada no es válida', (string) $this->client->getResponse()->getContent());
    }

    public function testExistingContactPageUsesTheComponentAndShowsTheDangerZone(): void
    {
        $this->login($this->getUser('alfa.profesor'));
        $id = (int) $this->connection()->fetchOne('SELECT id FROM itp_contact ORDER BY id LIMIT 1');
        self::assertGreaterThan(0, $id, 'Las fixtures tienen contactos de FFEOE');

        $component = $this->componentFor("/formacion/contacto/$id");

        self::assertCount(1, $component['crawler']->filter('textarea[name="contact[detail]"]'));
        self::assertCount(1, $component['crawler']->filter('input[name="items[]"][value="' . $id . '"]'), 'La zona de peligro envía el contacto a la operación de borrado');
    }

    public function testComponentRequestsAreDeniedToAnotherOrganization(): void
    {
        $this->login($this->getUser('alfa.profesor'));
        $component = $this->componentFor('/formacion/contacto/nuevo');

        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['contact.detail' => 'x']);

        self::assertResponseStatusCodeSame(403);
    }
}
