<?php

namespace App\Tests\Functional;

class TravelRouteApiTest extends AppWebTestCase
{
    public function testQueryWithoutTermReturnsJson(): void
    {
        $this->login($this->getAdmin());
        $this->client->request('GET', '/api/travel_route/query');

        self::assertResponseIsSuccessful();
        self::assertJson($this->client->getResponse()->getContent());
    }

    public function testQueryWithTermReturnsJson(): void
    {
        $this->login($this->getAdmin());
        $this->client->request('GET', '/api/travel_route/query?q=a');

        self::assertResponseIsSuccessful();
        self::assertJson($this->client->getResponse()->getContent());
    }
}
