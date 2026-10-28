<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;

/**
 * Listado y búsqueda de los valores de una escala de calificación. La escala de las fixtures tiene tres valores:
 * 0 "Insuficiente", 1 "Suficiente" y 2 "Notable".
 */
class PerformanceScaleValueListTest extends AppWebTestCase
{
    private function scaleId(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) static::getContainer()->get('doctrine')->getConnection()->fetchOne(
            'SELECT id FROM edu_performance_scale WHERE organization_id = ? LIMIT 1',
            [$this->getOrganization($code)->getId()]
        );
    }

    /**
     * @return list<string> valores que aparecen en la página
     */
    private function valuesFor(string $query = ''): array
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', '/centro/escala/valor/' . $this->scaleId() . '/listar/1/', $query === '' ? [] : ['q' => $query]);
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();

        return array_values(array_filter(
            ['Insuficiente', 'Suficiente', 'Notable'],
            static fn(string $value): bool => preg_match('/(?<![a-z])' . $value . '/', $content) === 1
        ));
    }

    public function testListShowsEveryValue(): void
    {
        self::assertSame(['Insuficiente', 'Suficiente', 'Notable'], $this->valuesFor());
    }

    public function testSearchByText(): void
    {
        self::assertSame(['Notable'], $this->valuesFor('Notab'));
    }

    public function testSearchByNumericGrade(): void
    {
        // buscar un número encuentra el valor con esa nota y los que lo contienen en su texto
        self::assertContains('Suficiente', $this->valuesFor('1'));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function awkwardSearchProvider(): iterable
    {
        yield 'texto sin resultados' => ['zzzz'];
        yield 'número sin resultados' => ['99'];
        yield 'comodín de SQL' => ['%'];
        yield 'comillas' => ["' OR 1=1 --"];
        yield 'etiqueta' => ['<script>alert(1)</script>'];
        yield 'texto muy largo' => [str_repeat('a', 5000)];
    }

    /**
     * @dataProvider awkwardSearchProvider
     */
    public function testAwkwardSearchesDoNotFail(string $query): void
    {
        $this->valuesFor($query);

        self::assertResponseIsSuccessful();
    }

    public function testTextSearchIsNotTreatedAsTheNumberZero(): void
    {
        // un texto que no es un número no debe encontrar el valor con nota 0 por una conversión implícita
        self::assertSame([], $this->valuesFor('zzzz'));
    }

    public function testSearchOnlyReturnsValuesOfTheScale(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $foreign = $this->scaleId(DemoData::ORG_B_CODE);

        $this->client->request('GET', "/centro/escala/valor/$foreign/listar/1/", ['q' => 'Notable']);

        self::assertResponseStatusCodeSame(403);
    }
}
