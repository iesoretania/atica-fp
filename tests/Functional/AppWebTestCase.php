<?php

namespace App\Tests\Functional;

use App\Entity\Organization;
use App\Entity\Person;
use App\Repository\OrganizationRepository;
use App\Repository\PersonRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Clase base de los tests funcionales. Usa los datos de demostración (docker/demo.sql)
 * cargados en la base de datos `*_test`. Cada test se ejecuta dentro de una transacción
 * que se revierte al terminar (DAMA).
 */
abstract class AppWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
    }

    protected function getAdmin(): Person
    {
        $person = static::getContainer()->get(PersonRepository::class)->findOneBy(['loginUsername' => 'admin']);
        self::assertInstanceOf(Person::class, $person, 'No existe el usuario "admin" en los datos de prueba');
        return $person;
    }

    /**
     * Devuelve una persona habilitada que no es administradora global
     */
    protected function getRegularUser(): Person
    {
        $person = static::getContainer()->get(PersonRepository::class)->findOneBy([
            'globalAdministrator' => false,
            'enabled' => true
        ]);
        self::assertInstanceOf(Person::class, $person);
        return $person;
    }

    protected function getOrganization(): Organization
    {
        $organization = static::getContainer()->get(OrganizationRepository::class)->findOneBy([]);
        self::assertInstanceOf(Organization::class, $organization);
        return $organization;
    }

    /**
     * Inicia sesión como el usuario indicado y selecciona la organización de la sesión
     */
    protected function login(Person $person, bool $withOrganization = true): void
    {
        $this->client->loginUser($person);

        if ($withOrganization) {
            // loginUser() crea la sesión con el token de seguridad: añadirle la organización seleccionada
            $session = static::getContainer()->get('session.factory')->createSession();
            $cookie = $this->client->getCookieJar()->get($session->getName());
            self::assertNotNull($cookie, 'loginUser() no ha creado la cookie de sesión');
            $session->setId($cookie->getValue());
            $session->start();
            $session->set('organization_id', $this->getOrganization()->getId());
            $session->save();
        }
    }
}
