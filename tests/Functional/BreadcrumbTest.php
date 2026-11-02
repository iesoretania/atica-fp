<?php

namespace App\Tests\Functional;

/**
 * Migas de pan: inicio > sección > subsección. Los datos de contexto (persona, empresa...) salen como subtítulo del
 * título y no se repiten en la miga.
 */
class BreadcrumbTest extends AppWebTestCase
{
    public function testContextGoesInTheSubtitleAndNotInTheTrail(): void
    {
        $this->login($this->getUser('alfa.admin'));
        $crawler = $this->client->request('GET', '/centro/profesorado/2');
        self::assertResponseIsSuccessful();

        $trail = $crawler->filter('main ol li')->each(fn ($n) => trim(preg_replace('/\s+/', ' ', $n->text())));
        self::assertSame(['I.E.S. Alfa', '>', 'Gestión del centro', '>', 'Profesorado'], $trail);
        self::assertSame('Datos del docente', trim($crawler->filter('main h1')->text()));
        self::assertSame('Directora Dirección Alfa', trim($crawler->filter('main h1 + p')->text()));
    }

    public function testContextAlreadyInTheTitleIsNotRepeated(): void
    {
        $this->login($this->getUser('alfa.admin'));
        $crawler = $this->client->request('GET', '/centro/ensenanza/materia/1/resultado/1/');
        self::assertResponseIsSuccessful();

        self::assertStringContainsString('Resultados de aprendizaje', $crawler->filter('main h1')->text());
        $trail = $crawler->filter('main ol')->text();
        self::assertStringNotContainsString('Resultados de aprendizaje', $trail);
        self::assertStringNotContainsString('Programación', $trail);
    }

    public function testHomeCrumbIsAnIconWithAnAccessibleName(): void
    {
        $this->login($this->getUser('alfa.admin'));
        $crawler = $this->client->request('GET', '/fct');
        $home = $crawler->filter('main ol li a[aria-label]')->first();
        self::assertSame('I.E.S. Alfa', $home->attr('aria-label'));
        self::assertSame('/', $home->attr('href'));
    }
}
