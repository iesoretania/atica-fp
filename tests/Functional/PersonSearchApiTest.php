<?php

namespace App\Tests\Functional;

/**
 * Búsqueda de personas de los selectores (tutores laborales, gerentes...): exacta por defecto y parcial con
 * APP_PERSON_SEARCH_PARTIAL=true.
 */
class PersonSearchApiTest extends AppWebTestCase
{
    private const URL = '/empresa/api/person/query';

    private mixed $previousServerValue = null;
    private mixed $previousEnvValue = null;

    protected function setUp(): void
    {
        $this->previousServerValue = $_SERVER['APP_PERSON_SEARCH_PARTIAL'] ?? null;
        $this->previousEnvValue = $_ENV['APP_PERSON_SEARCH_PARTIAL'] ?? null;
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // se restaura el valor de .env: otros tests lo necesitan
        $this->previousServerValue === null ? $this->unsetValue($_SERVER) : $_SERVER['APP_PERSON_SEARCH_PARTIAL'] = $this->previousServerValue;
        $this->previousEnvValue === null ? $this->unsetValue($_ENV) : $_ENV['APP_PERSON_SEARCH_PARTIAL'] = $this->previousEnvValue;
        parent::tearDown();
    }

    private function unsetValue(array &$globals): void
    {
        unset($globals['APP_PERSON_SEARCH_PARTIAL']);
    }

    private function enablePartialSearch(): void
    {
        $_SERVER['APP_PERSON_SEARCH_PARTIAL'] = $_ENV['APP_PERSON_SEARCH_PARTIAL'] = 'true';
    }

    /**
     * @return list<array{id: int, text: string}>
     */
    private function search(string $term): array
    {
        $this->login($this->getAdmin());
        $this->client->request('GET', self::URL, ['q' => $term, 'page_limit' => 10]);
        self::assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<array{id: int, text: string}> $results
     * @return list<string>
     */
    private function texts(array $results): array
    {
        return array_map(static fn (array $r): string => $r['text'], array_filter($results, static fn (array $r): bool => $r['id'] !== 0));
    }

    public function testExactSearchIsTheDefault(): void
    {
        $found = $this->search('alfa.tutorlaboral');
        self::assertSame(['Tutor Laboral Uno Alfa'], $this->texts($found));

        // una parte del nombre no encuentra a nadie: solo se ofrece crear la persona
        $partial = $this->search('Laboral Uno');
        self::assertSame([], $this->texts($partial));
        self::assertSame(0, $partial[0]['id']);
    }

    public function testPartialSearchFindsByNameSurnameAndIdentifier(): void
    {
        $this->enablePartialSearch();

        // varias palabras, en cualquier orden y sin distinguir mayúsculas
        self::assertSame(['Tutor Laboral Uno Alfa'], $this->texts($this->search('laboral uno alfa')));
        self::assertSame(['Tutor Laboral Uno Alfa'], $this->texts($this->search('alfa TUTOR uno')));

        // varias personas por una parte común
        $texts = $this->texts($this->search('Laboral Dos'));
        self::assertContains('Tutora Laboral Dos Alfa', $texts);
        self::assertContains('Tutora Laboral Dos Beta', $texts);

        // por identificador parcial
        $tutor = $this->getUser('alfa.tutorlaboral');
        $tutor->setUniqueIdentifier('12345678Z');
        static::getContainer()->get('doctrine')->getManager()->flush();
        self::assertSame(['Tutor Laboral Uno Alfa - 12345678Z'], $this->texts($this->search('5678')));

        // el usuario completo sigue funcionando
        self::assertSame(['Tutor Laboral Uno Alfa - 12345678Z'], $this->texts($this->search('alfa.tutorlaboral')));
    }

    public function testPartialSearchAlwaysOffersToCreateAPerson(): void
    {
        $this->enablePartialSearch();

        $results = $this->search('Laboral Uno Alfa');
        self::assertSame(0, end($results)['id'], 'La opción de crear persona va al final');

        $none = $this->search('zzzz-no-existe');
        self::assertCount(1, $none);
        self::assertSame(0, $none[0]['id']);
    }

    public function testWildcardsAreSearchedAsText(): void
    {
        $this->enablePartialSearch();

        self::assertSame([], $this->texts($this->search('%')));
        self::assertSame([], $this->texts($this->search('_____')));
    }

    public function testSearchBoxShowsWhatToType(): void
    {
        $this->login($this->getAdmin());

        $crawler = $this->client->request('GET', '/empresa/nueva');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('completos', $crawler->filter('select[name$="[manager]"]')->attr('data-entity-select-hint-value'));

        $this->enablePartialSearch();
        $crawler = $this->client->request('GET', '/empresa/nueva');
        self::assertStringContainsString('parte del nombre', $crawler->filter('select[name$="[manager]"]')->attr('data-entity-select-hint-value'));
    }
}
