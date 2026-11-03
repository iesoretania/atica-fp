<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;

/**
 * Detecta consultas N+1: en las pantallas con listas (alumnado, profesorado, asignaciones docentes, estancias...)
 * ninguna consulta SQL puede repetirse más de unas pocas veces y el total no puede dispararse. Las consultas de
 * la barra del depurador (information_schema, migraciones) no se cuentan.
 */
class QueryCountTest extends AppWebTestCase
{
    private const MAX_REPEATS = 4;
    private const MAX_QUERIES = 45;

    /**
     * @return iterable<string, array{string}>
     */
    public static function pageProvider(): iterable
    {
        foreach ([
            '/centro/profesorado/listar/1',
            '/centro/materia/listar/1',
            '/centro/grupo/listar/1',
            '/centro/grupo/2',
            '/fct/acuerdo/estudiante/1',
            '/formacion/plan/grupo/1',
            '/formacion/plan/curso/grupo/estudiante/listar/1',
            '/dual/contacto/listar/1',
            '/dual/reunion/listar/1',
        ] as $url) {
            yield $url => [$url];
        }
    }

    /**
     * @dataProvider pageProvider
     */
    public function testPageDoesNotRunOneQueryPerRow(string $url): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        // la primera petición del proceso calienta cachés (metadatos, plantillas): no cuenta
        $this->client->request('GET', '/');
        $this->client->enableProfiler();
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();

        $counts = [];
        foreach ($this->client->getProfile()->getCollector('db')->getQueries() as $connectionQueries) {
            foreach ($connectionQueries as $query) {
                $sql = trim(preg_replace('/\s+/', ' ', (string) $query['sql']));
                if (preg_match('/information_schema|DATABASE\(\)|migration_versions|^"/i', $sql)) {
                    continue;
                }
                $counts[$sql] = ($counts[$sql] ?? 0) + 1;
            }
        }
        arsort($counts);
        $worst = (int) reset($counts);
        self::assertLessThanOrEqual(
            self::MAX_REPEATS,
            $worst,
            sprintf("%s repite %d veces una consulta (¿N+1?): %s", $url, $worst, substr((string) array_key_first($counts), 0, 300))
        );
        self::assertLessThanOrEqual(self::MAX_QUERIES, array_sum($counts), $url . ' lanza demasiadas consultas');
    }
}
