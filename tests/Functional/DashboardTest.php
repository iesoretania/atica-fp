<?php

namespace App\Tests\Functional;

/**
 * Escritorio de la página de inicio: paneles de seguimiento de FCT y FFEOE según el rol.
 */
class DashboardTest extends AppWebTestCase
{
    public static function userProvider(): iterable
    {
        foreach ([
            'alfa.admin', 'alfa.economico', 'alfa.jefe', 'alfa.coordinador', 'alfa.gestorffe', 'alfa.tutor',
            'alfa.profesor', 'alfa.alumno1', 'alfa.tutorlaboral',
        ] as $username) {
            yield $username => [$username];
        }
    }

    /**
     * @dataProvider userProvider
     */
    public function testFrontPageRendersForEveryRole(string $username): void
    {
        $this->login($this->getUser($username));
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testManagerSeesBothTrackingPanelsWithLinks(): void
    {
        $this->login($this->getUser('alfa.admin'));
        $crawler = $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        self::assertGreaterThanOrEqual(1, $crawler->filter('main section')->count());
        self::assertGreaterThanOrEqual(1, $crawler->filter('main section a[href*="seguimiento"]')->count());
    }

    public function testPersonalDataIsNotOfferedInTheMainMenuCards(): void
    {
        $this->login($this->getUser('alfa.profesor'));
        $crawler = $this->client->request('GET', '/');
        self::assertSame(0, $crawler->filter('main a.group[href$="/datos-personales"], main a.group[href*="personal"]')->count());
    }
}
