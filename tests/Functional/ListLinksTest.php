<?php

namespace App\Tests\Functional;

/**
 * Los listados no dejan código de plantilla sin evaluar en los enlaces (por ejemplo, el botón «Nuevo...»)
 */
class ListLinksTest extends AppWebTestCase
{
    public function testNewButtonOfTheCentreListsPointsToTheForm(): void
    {
        $this->login($this->getUser('alfa.admin'));

        $checked = 0;
        foreach ([
            '/centro/encuesta/listar' => '/centro/encuesta/nueva',
            '/centro/departamento/listar' => '/centro/departamento/nuevo',
            '/centro/grupo/listar' => '/centro/grupo/nuevo',
            '/centro/ensenanza/listar' => '/centro/ensenanza/nuevo',
            '/centro/plantilla/listar' => '/centro/plantilla/nueva',
            '/centro/nivel/listar' => '/centro/nivel/nuevo',
        ] as $list => $expected) {
            $crawler = $this->client->request('GET', $list);
            self::assertResponseIsSuccessful($list);
            $hrefs = $crawler->filter('main a')->each(fn ($a) => $a->attr('href'));
            self::assertContains($expected, $hrefs, 'Falta el enlace de alta en ' . $list);
            $checked++;
        }
        self::assertSame(6, $checked);
    }

    public function testNoListLinkContainsTemplateCode(): void
    {
        $this->login($this->getUser('alfa.admin'));
        $router = static::getContainer()->get('router');

        $visited = 0;
        foreach ($router->getRouteCollection() as $name => $route) {
            // listados sin parámetros obligatorios (el número de página es opcional)
            if (!str_ends_with($name, '_list') || !in_array('GET', $route->getMethods() ?: ['GET'], true)) {
                continue;
            }
            $required = array_diff($route->compile()->getVariables(), array_keys($route->getDefaults()));
            if ($required !== []) {
                continue;
            }

            $crawler = $this->client->request('GET', $router->generate($name));
            if ($this->client->getResponse()->getStatusCode() !== 200) {
                continue;
            }
            $visited++;
            foreach ($crawler->filter('main a')->each(fn ($a) => (string) $a->attr('href')) as $href) {
                self::assertStringNotContainsString('{%', $href, $name);
                self::assertStringNotContainsString('{{', $href, $name);
            }
        }

        self::assertGreaterThan(10, $visited);
    }
}
