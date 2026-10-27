<?php

namespace App\Tests\Functional;

use App\Entity\Organization;
use App\Entity\Person;
use App\Repository\OrganizationRepository;
use App\Repository\PersonRepository;
use App\Tests\Fixtures\DemoData;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Clase base de los tests funcionales. Usa los datos de las fixtures (tests/Fixtures)
 * cargados en la base de datos `*_test` con `composer test:reset-db`. Cada test se ejecuta dentro de una transacción
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
        return $this->getUser(DemoData::USER_ADMIN);
    }

    /**
     * Profesor de la organización A sin permisos de administración
     */
    protected function getRegularUser(): Person
    {
        return $this->getUser(DemoData::USER_A_TEACHER);
    }

    protected function getUser(string $username): Person
    {
        $person = static::getContainer()->get(PersonRepository::class)->findOneBy(['loginUsername' => $username]);
        self::assertInstanceOf(Person::class, $person, sprintf('No existe el usuario "%s" en los datos de prueba', $username));
        return $person;
    }

    protected function getOrganization(string $code = DemoData::ORG_A_CODE): Organization
    {
        $organization = static::getContainer()->get(OrganizationRepository::class)->findOneBy(['code' => $code]);
        self::assertInstanceOf(Organization::class, $organization);
        return $organization;
    }

    /**
     * Inicia sesión como el usuario indicado y selecciona la organización de la sesión
     */
    protected function login(Person $person, bool $withOrganization = true, string $organizationCode = DemoData::ORG_A_CODE): void
    {
        $this->client->loginUser($person);

        if ($withOrganization) {
            // loginUser() crea la sesión con el token de seguridad: añadirle la organización seleccionada
            $session = static::getContainer()->get('session.factory')->createSession();
            $cookie = $this->client->getCookieJar()->get($session->getName());
            self::assertNotNull($cookie, 'loginUser() no ha creado la cookie de sesión');
            $session->setId($cookie->getValue());
            $session->start();
            $session->set('organization_id', $this->getOrganization($organizationCode)->getId());
            $session->save();
        }
    }

    /**
     * Dirección IP distinta en cada llamada: el limitador de intentos de entrada guarda su estado en la caché
     * durante 15 minutos, y los tests no deben depender de ejecuciones anteriores.
     */
    protected function uniqueIp(): string
    {
        return '10.' . random_int(1, 250) . '.' . random_int(1, 250) . '.' . random_int(1, 250);
    }

    /**
     * Entra en la aplicación por la pantalla de acceso, con las credenciales y la cadena de redirecciones reales
     * (a diferencia de login(), que crea la sesión directamente), y devuelve la última respuesta como página.
     */
    protected function signIn(string $username, string $password = DemoData::PASSWORD): void
    {
        $crawler = $this->client->request('GET', '/entrar');
        $this->client->request('POST', '/comprobar', [
            '_username' => $username,
            '_password' => $password,
            '_csrf_token' => $crawler->filter('input[name=_csrf_token]')->attr('value'),
        ], [], ['REMOTE_ADDR' => $this->uniqueIp()]);

        for ($i = 0; $i < 4 && $this->client->getResponse()->isRedirect(); $i++) {
            $this->client->followRedirect();
        }
    }
}
