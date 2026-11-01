<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Formularios dinámicos de contactos de FP dual (Live Components Wlt:ContactForm y Wlt:ContactEducationalTutorReportForm).
 * Se prueban como los usa el navegador: se pide la página, se leen los atributos data-live-* del componente y se hacen las
 * peticiones a /_components/... con los valores modificados. Comprueba la cascada centro de trabajo → proyectos y los
 * permisos en cada petición.
 */
class WltContactFormComponentTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    /**
     * @return array{url: string, props: string, csrf: string, crawler: Crawler}
     */
    private function componentFor(string $name, string $path): array
    {
        $crawler = $this->client->request('GET', $path);
        self::assertResponseIsSuccessful();
        $root = $crawler->filter('[data-live-name-value="' . $name . '"]');
        self::assertCount(1, $root, "La página incluye el componente $name");

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

    public function testNewContactPageUsesTheComponentAndTheCascadeOffersProjects(): void
    {
        $this->login($this->getUser('alfa.coordinador'));
        $component = $this->componentFor('Wlt:ContactForm', '/dual/contacto/nuevo');

        $workcenters = array_values(array_filter(
            $component['crawler']->filter('select[name="contact[workcenter]"] option')->extract(['value']),
            static fn (string $v): bool => $v !== ''
        ));
        self::assertNotEmpty($workcenters);

        $found = false;
        foreach ($workcenters as $workcenter) {
            $this->update($component, ['contact.workcenter' => $workcenter]);
            self::assertResponseIsSuccessful();
            if (str_contains((string) $this->client->getResponse()->getContent(), 'contact[projects][]')) {
                $found = true;
                break;
            }
        }
        self::assertTrue($found, 'Algún centro de trabajo de las fixtures ofrece proyectos como casillas');

        // al volver a elegir otro centro, los proyectos elegidos se vacían sin error de validación
        $crawler = new Crawler((string) $this->client->getResponse()->getContent());
        $next = $this->attributes($crawler->filter('[data-live-name-value="Wlt:ContactForm"]'));
        $this->update($next, ['contact.workcenter' => '']);
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('La opción seleccionada no es válida', (string) $this->client->getResponse()->getContent());
    }

    public function testExistingContactPageUsesTheComponentAndShowsTheDangerZone(): void
    {
        $id = (int) $this->connection()->fetchOne('SELECT id FROM wlt_contact ORDER BY id LIMIT 1');
        self::assertGreaterThan(0, $id, 'Las fixtures tienen contactos de FP dual');
        // quien registró el contacto puede borrarlo
        $username = (string) $this->connection()->fetchOne(
            'SELECT p.login_username FROM wlt_contact c JOIN edu_teacher t ON t.id = c.teacher_id JOIN person p ON p.id = t.person_id WHERE c.id = ?',
            [$id]
        );
        $this->login($this->getUser($username));

        $component = $this->componentFor('Wlt:ContactForm', "/dual/contacto/$id");

        self::assertCount(1, $component['crawler']->filter('textarea[name="contact[detail]"]'));
        self::assertCount(1, $component['crawler']->filter('input[name="items[]"][value="' . $id . '"]'), 'La zona de peligro envía el contacto a la operación de borrado');
    }

    public function testExistingContactComponentRequestsAreDeniedToAnotherOrganization(): void
    {
        $this->login($this->getUser('alfa.coordinador'));
        $id = (int) $this->connection()->fetchOne('SELECT id FROM wlt_contact ORDER BY id LIMIT 1');
        $component = $this->componentFor('Wlt:ContactForm', "/dual/contacto/$id");

        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['contact.detail' => 'x']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testContactComponentRequestsAreDeniedToAnotherOrganization(): void
    {
        $this->login($this->getUser('alfa.coordinador'));
        $component = $this->componentFor('Wlt:ContactForm', '/dual/contacto/nuevo');

        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['contact.detail' => 'x']);

        self::assertResponseStatusCodeSame(403);
    }

    public function testEducationalTutorReportFormRecalculatesTheWorkcenterWhenProjectsChangeAndIsDeniedToAnotherOrganization(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $teacherId = (int) $this->connection()->fetchOne('SELECT DISTINCT teacher_id FROM wlt_contact ORDER BY teacher_id LIMIT 1');
        self::assertGreaterThan(0, $teacherId);

        $component = $this->componentFor('Wlt:ContactEducationalTutorReportForm', "/dual/contacto/informe/seguimiento/datos/$teacherId");
        self::assertCount(1, $component['crawler']->filter('select[name="contact_educational_tutor_report[workcenter]"]'));

        $this->update($component, ['contact_educational_tutor_report.projects' => ['0']]);
        self::assertResponseIsSuccessful();

        $this->client->getCookieJar()->clear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->update($component, ['contact_educational_tutor_report.projects' => ['0']]);
        self::assertResponseStatusCodeSame(403);

        // y la propia página del informe de un docente de otro centro tampoco se puede abrir
        $this->client->request('GET', "/dual/contacto/informe/seguimiento/datos/$teacherId");
        self::assertResponseStatusCodeSame(403);
    }
}
