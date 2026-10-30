<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Informes de contactos de FP dual en PDF: seguimiento por profesor y por centro de trabajo, con sus listados.
 * Los obtienen la administración, el coordinador y los jefes de departamento para sus grupos, y cada profesor
 * solo el suyo. La fixture tiene un contacto del tutor docente "tutor" con el centro de trabajo del acuerdo 1.
 */
class WltContactReportTest extends AppWebTestCase
{
    private const BASE = '/dual/contacto/informe';

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

    private function workcenterId(): int
    {
        return (int) $this->connection()->fetchOne('SELECT workcenter_id FROM wlt_contact WHERE teacher_id = ? ORDER BY id LIMIT 1', [$this->teacherId('alfa.tutor')]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'coordinador' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenTheLists(string $username): void
    {
        $this->login($this->getUser($username));

        foreach ([self::BASE . '/seguimiento/' . $this->year() . '/1', self::BASE . '/centros/' . $this->year() . '/1'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($username . ' ' . $url);
        }
    }

    public function testAdministratorGetsTheReportsAsPdf(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach ([
            self::BASE . '/seguimiento/datos/' . $this->teacherId('alfa.tutor'),
            self::BASE . '/centros/datos/' . $this->workcenterId() . '/' . $this->year(),
        ] as $url) {
            $crawler = $this->client->request('GET', $url);
            $this->client->submit($crawler->filter('form')->last()->form());

            self::assertResponseIsSuccessful($url);
            self::assertStringContainsString('application/pdf', (string) $this->client->getResponse()->headers->get('Content-Type'), $url);
        }
    }

    public function testListsFilterBySearch(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach (['seguimiento', 'centros'] as $report) {
            $this->client->request('GET', self::BASE . "/$report/" . $this->year() . '/1', ['q' => 'zzzz-no-existe']);
            self::assertResponseIsSuccessful($report);
        }
    }

    public function testTeacherOnlyGetsTheirOwnReport(): void
    {
        $this->login($this->getUser('alfa.tutor'));

        $this->client->request('GET', self::BASE . '/seguimiento/' . $this->year() . '/1');
        self::assertResponseIsSuccessful();
        $this->client->request('GET', self::BASE . '/seguimiento/datos/' . $this->teacherId('alfa.tutor'));
        self::assertResponseIsSuccessful();

        // no puede pedir el informe de otro profesor
        $this->client->request('GET', self::BASE . '/seguimiento/datos/' . $this->teacherId('alfa.profesor'));
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['alumno5', 'tutorlaboral', 'economico', 'gestorffe'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotGetTheReports(string $username): void
    {
        $this->login($this->getUser($username));

        foreach ([
            self::BASE . '/seguimiento/' . $this->year() . '/1',
            self::BASE . '/seguimiento/datos/' . $this->teacherId('alfa.tutor'),
            self::BASE . '/centros/' . $this->year() . '/1',
            self::BASE . '/centros/datos/' . $this->workcenterId() . '/' . $this->year(),
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $url);
        }
    }

    public function testAdministratorOfAnotherCentreCannotGetThem(): void
    {
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ([
            self::BASE . '/seguimiento/' . $this->year() . '/1',
            self::BASE . '/seguimiento/datos/' . $this->teacherId('alfa.tutor'),
            self::BASE . '/centros/' . $this->year() . '/1',
            self::BASE . '/centros/datos/' . $this->workcenterId() . '/' . $this->year(),
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }
}
