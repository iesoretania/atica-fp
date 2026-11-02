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

    public function testStudentSeesOnlyTheirOwnProgress(): void
    {
        $this->login($this->getUser('alfa.alumno1'));
        $crawler = $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        $text = $crawler->filter('main')->text();
        self::assertStringContainsString('Mis prácticas', $text);
        self::assertStringNotContainsString('Empiezan pronto', $text);
        self::assertStringNotContainsString('Convenios sin firmar', $text);
    }

    public function testManagementSeesTeamIndicatorsAndUnsignedAgreementsOnlyInFct(): void
    {
        $this->login($this->getUser('alfa.admin'));
        $crawler = $this->client->request('GET', '/');
        $panels = $crawler->filter('main section')->each(fn ($n) => $n->text());
        self::assertCount(3, $panels);
        // Orden de los paneles: FFEOE, FCT y FP dual
        self::assertStringContainsString('Empiezan pronto', $panels[1]);
        self::assertStringContainsString('Con faltas sin justificar', $panels[1]);
        self::assertStringContainsString('Convenios sin firmar', $panels[1]);
        self::assertStringNotContainsString('Convenios sin firmar', $panels[0]);
        self::assertStringNotContainsString('Convenios sin firmar', $panels[2]);
    }

    public function testTeachersDoNotSeeUnsignedAgreements(): void
    {
        $this->login($this->getUser('alfa.profesor'));
        $crawler = $this->client->request('GET', '/');
        $text = $crawler->filter('main')->text();
        self::assertStringContainsString('Con faltas sin justificar', $text);
        self::assertStringNotContainsString('Convenios sin firmar', $text);
    }

    public static function datedTablesProvider(): iterable
    {
        yield 'FCT' => ['FCT', 'wpt_agreement'];
        yield 'FFEOE' => ['FFEOE', 'itp_student_program_workcenter'];
        yield 'FP dual' => ['Dual', 'wlt_agreement'];
    }

    /**
     * Los contadores dependen de las fechas: se cambian las de todos los convenios y se comprueba que las cifras se
     * reparten entre «en prácticas ahora», «empiezan pronto» y «finalizadas sin cerrar».
     *
     * @dataProvider datedTablesProvider
     */
    public function testCountersFollowTheDatesOfTheAssignments(string $panelTitle, string $table): void
    {
        $connection = static::getContainer()->get('doctrine')->getConnection();
        $today = new \DateTimeImmutable('today');
        $setDates = static fn (string $from, string $to) => $connection->executeStatement(
            "UPDATE $table SET start_date = ?, end_date = ?",
            [$today->modify($from)->format('Y-m-d'), $today->modify($to)->format('Y-m-d')]
        );
        $this->login($this->getUser('alfa.admin'));

        $setDates('-5 days', '+5 days');
        $now = $this->counters($panelTitle);
        self::assertGreaterThan(0, $now['En prácticas ahora'], 'el administrador de demostración tiene asignaciones');
        self::assertSame(0, $now['Empiezan pronto']);

        $setDates('+5 days', '+30 days');
        $soon = $this->counters($panelTitle);
        self::assertSame(0, $soon['En prácticas ahora']);
        self::assertSame($now['En prácticas ahora'], $soon['Empiezan pronto']);

        $setDates('+60 days', '+90 days');
        $later = $this->counters($panelTitle);
        self::assertSame(0, $later['En prácticas ahora']);
        self::assertSame(0, $later['Empiezan pronto']);

        $setDates('-60 days', '-1 day');
        $past = $this->counters($panelTitle);
        self::assertSame(0, $past['En prácticas ahora']);
        self::assertSame(0, $past['Empiezan pronto']);
        self::assertGreaterThan(0, $past['Finalizadas sin cerrar'], 'con jornadas sin bloquear');
        self::assertLessThanOrEqual($now['En prácticas ahora'], $past['Finalizadas sin cerrar']);
    }

    /** @return array<string, int> cifra de cada contador del panel cuyo título empieza por $panelTitle */
    private function counters(string $panelTitle): array
    {
        $crawler = $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        $counters = [];
        foreach ($crawler->filter('main section') as $section) {
            $node = new \Symfony\Component\DomCrawler\Crawler($section);
            if (!str_starts_with(trim($node->filter('h2')->text()), $panelTitle)) {
                continue;
            }
            foreach ($node->filter('dl a') as $tile) {
                $tile = new \Symfony\Component\DomCrawler\Crawler($tile);
                $counters[trim($tile->filter('dt')->text())] = (int) trim($tile->filter('dd')->text());
            }
        }
        self::assertNotEmpty($counters, "no hay panel «$panelTitle»");

        return $counters;
    }

    public static function genderProvider(): iterable
    {
        yield 'sin especificar' => [0, 'Te damos la bienvenida'];
        yield 'hombre' => [1, 'Bienvenido,'];
        yield 'mujer' => [2, 'Bienvenida,'];
    }

    /**
     * @dataProvider genderProvider
     */
    public function testFrontPageGreetsThePersonByNameAndGender(int $gender, string $expected): void
    {
        $user = $this->getUser('alfa.profesor');
        $user->setGender($gender);
        static::getContainer()->get('doctrine')->getManager()->flush();
        $this->login($user);
        $crawler = $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
        $heading = $crawler->filter('main h2')->first()->text();
        self::assertStringContainsString($expected, $heading);
        self::assertStringContainsString($user->getFirstName(), $heading);
        self::assertMatchesRegularExpression('/Buenos días|Buenas tardes|Buenas noches/', $crawler->filter('main')->text());
        // el menú ya está en la barra lateral: la portada no repite las tarjetas
        self::assertSame(0, $crawler->filter('main a.group')->count());
    }

    public function testPersonalDataIsNotOfferedInTheMainMenuCards(): void
    {
        $this->login($this->getUser('alfa.profesor'));
        $crawler = $this->client->request('GET', '/');
        self::assertSame(0, $crawler->filter('main a.group[href$="/datos-personales"], main a.group[href*="personal"]')->count());
    }
}
