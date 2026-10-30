<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Administración de los cursos académicos del centro: listado, alta, edición, cambio del curso actual, copia y
 * borrado. Solo la administración del centro puede, y solo sobre los cursos de su propio centro.
 */
class AcademicYearAdminTest extends AppWebTestCase
{
    private const BASE = '/centro/cursoacademico';

    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function yearId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->getOrganization($code)->getCurrentAcademicYear()->getId();
    }

    private function yearCount(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM edu_academic_year WHERE organization_id = ?', [$this->getOrganization($code)->getId()]);
    }

    private function currentYear(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne('SELECT current_academic_year_id FROM organization WHERE id = ?', [$this->getOrganization($code)->getId()]);
    }

    /**
     * Crea un curso académico nuevo con el formulario real y devuelve su identificador
     */
    private function createYear(string $description = '2027-2028'): int
    {
        $crawler = $this->client->request('GET', self::BASE . '/nuevo');
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'academic_year[description]' => $description,
            'academic_year[startDate]' => '2027-09-01',
            'academic_year[endDate]' => '2028-08-31',
        ]));
        self::assertResponseRedirects();

        return (int) $this->connection()->fetchOne('SELECT id FROM edu_academic_year WHERE description = ? AND organization_id = ?', [$description, $this->getOrganization()->getId()]);
    }

    public function testAdministratorOpensTheScreens(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach ([self::BASE . '/listar', self::BASE . '/nuevo', self::BASE . '/' . $this->yearId(), self::BASE . '/copiar/' . $this->yearId()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['director', 'jefe', 'tutor', 'profesor', 'coordinador', 'gestorffe', 'economico', 'alumno1'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotManageAcademicYears(string $username): void
    {
        $year = $this->yearId();
        $before = $this->yearCount();
        $this->login($this->getUser($username));

        foreach ([self::BASE . '/listar', self::BASE . '/nuevo', self::BASE . "/$year", self::BASE . "/copiar/$year"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $url);
        }
        $this->client->request('POST', self::BASE . '/operacion', ['items' => [$year], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', self::BASE . '/operacion', ['switch' => $year]);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->yearCount());
    }

    public function testAdministratorOfAnotherCentreCannotTouchThem(): void
    {
        $year = $this->yearId();
        $before = $this->yearCount();
        $current = $this->currentYear();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ([self::BASE . "/$year", self::BASE . "/copiar/$year"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', self::BASE . '/operacion', ['items' => [$year], 'confirm' => 'ok']);
        $this->client->request('POST', self::BASE . '/operacion', ['switch' => $year]);

        self::assertSame($before, $this->yearCount());
        self::assertSame($current, $this->currentYear());
        self::assertNotSame($year, $this->currentYear(DemoData::ORG_B_CODE));
    }

    public function testListFiltersBySearch(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', self::BASE . '/listar', ['q' => '2026-2027']);
        self::assertStringContainsString('2026-2027', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', self::BASE . '/listar', ['q' => 'zzzz-no-existe']);
        self::assertStringNotContainsString('2026-2027</', (string) $this->client->getResponse()->getContent());
    }

    public function testAdministratorCreatesAndEditsAnAcademicYear(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->yearCount();

        $id = $this->createYear();

        self::assertSame($before + 1, $this->yearCount());
        $crawler = $this->client->request('GET', self::BASE . "/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['academic_year[description]' => '2027-2028 editado']));
        self::assertResponseRedirects();
        self::assertSame('2027-2028 editado', $this->connection()->fetchOne('SELECT description FROM edu_academic_year WHERE id = ?', [$id]));
    }

    public function testAcademicYearWithoutDescriptionDoesNotFail(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->yearCount();

        $crawler = $this->client->request('GET', self::BASE . '/nuevo');
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'academic_year[description]' => '',
            'academic_year[startDate]' => '2027-09-01',
            'academic_year[endDate]' => '2028-08-31',
        ]));

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($before, $this->yearCount(), 'No se crea un curso sin descripción');
    }

    public function testAcademicYearEndingBeforeItStartsIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->yearCount();

        $crawler = $this->client->request('GET', self::BASE . '/nuevo');
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'academic_year[description]' => 'Zz curso al revés',
            'academic_year[startDate]' => '2028-08-31',
            'academic_year[endDate]' => '2027-09-01',
        ]));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->yearCount());
    }

    public function testCurrentAcademicYearIsSwitched(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $original = $this->currentYear();
        $new = $this->createYear();

        $this->client->request('POST', self::BASE . '/operacion', ['switch' => $new]);

        self::assertResponseRedirects();
        self::assertSame($new, $this->currentYear());
        self::assertNotSame($original, $this->currentYear());
    }

    public function testDeletionRequiresConfirmationAndNeverRemovesTheCurrentYear(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $current = $this->yearId();
        $new = $this->createYear();
        $before = $this->yearCount();

        $this->client->request('POST', self::BASE . '/operacion', ['items' => [$new, $current]]);
        self::assertResponseIsSuccessful();
        self::assertSame($before, $this->yearCount());

        $this->client->request('POST', self::BASE . '/operacion', ['items' => [$new, $current], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame($before - 1, $this->yearCount());
        self::assertSame(1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM edu_academic_year WHERE id = ?', [$current]), 'El curso actual no se borra');
    }

    public function testCopyFromAnotherYear(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $source = $this->yearId();
        $target = $this->createYear();

        $crawler = $this->client->request('GET', self::BASE . "/copiar/$target");
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Copiar')->count() > 0 ? $crawler->selectButton('Copiar')->form() : $crawler->filter('form')->last()->form();
        $form['academic_year_copy[academicYear]']->select((string) $source);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertGreaterThan(0, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM edu_training WHERE academic_year_id = ?', [$target]));
    }
}
