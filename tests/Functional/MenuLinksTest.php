<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;

/**
 * Todo enlace que el menú ofrece a una persona debe poder abrirse: una entrada de menú que acaba en un 403
 * es un enlace roto para quien la ve.
 */
class MenuLinksTest extends AppWebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
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
    public function testEveryMenuLinkIsAccessible(string $username): void
    {
        $this->login($this->getUser($username));

        $links = $this->menuLinks('/');
        // las pantallas de inicio de cada apartado (las tarjetas de «Gestión del centro», de cada módulo...)
        foreach ($links as $link) {
            foreach ($this->menuLinks($link) as $child) {
                $links[$child] = $child;
            }
        }

        self::assertNotEmpty($links);
        $denied = [];
        foreach ($links as $link) {
            $this->client->request('GET', $link);
            $status = $this->client->getResponse()->getStatusCode();
            if ($status === 403) {
                $denied[] = $link;
            }
        }

        self::assertSame([], $denied, "El menú de $username ofrece enlaces a los que no tiene acceso");
    }

    /**
     * @return array<string, string> enlaces internos del menú y de las tarjetas de la página, por ruta
     */
    private function menuLinks(string $path): array
    {
        $crawler = $this->client->request('GET', $path);
        if ($this->client->getResponse()->getStatusCode() >= 400) {
            return [];
        }

        $links = [];
        foreach ($crawler->filter('nav a[href], main a.card[href], main .card a[href], main .menu a[href]') as $anchor) {
            $href = $anchor->getAttribute('href');
            if ($href === '' || $href === '#' || !str_starts_with($href, '/') || str_contains($href, 'salir') || str_contains($href, '_switch_user')) {
                continue;
            }
            $links[$href] = $href;
        }

        return $links;
    }
}
