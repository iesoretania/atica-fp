<?php

namespace App\Tests\Functional;

use App\Entity\EventLog;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Registro de actividad (EventLog): accesos, entradas correctas, intentos fallidos y suplantaciones.
 */
class ActivityLogTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    /**
     * @return list<EventLog>
     */
    private function entries(string $event): array
    {
        return $this->em()->getRepository(EventLog::class)->findBy(['event' => $event], ['id' => 'ASC']);
    }

    private function logCount(): int
    {
        return $this->em()->getRepository(EventLog::class)->count([]);
    }

    public function testAuthenticatedRequestIsLoggedWithUserPathAndAddress(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $before = count($this->entries(EventLog::ACCESS));

        $this->client->request('GET', '/datos', [], [], ['REMOTE_ADDR' => '10.9.8.7']);

        $entries = $this->entries(EventLog::ACCESS);
        self::assertCount($before + 1, $entries);
        $last = end($entries);
        self::assertSame('/datos', $last->getData());
        self::assertSame('10.9.8.7', $last->getIp());
        self::assertSame($this->getUser(DemoData::USER_A_TEACHER)->getId(), $last->getUser()?->getId());
    }

    public function testAnonymousRequestIsLoggedWithoutUser(): void
    {
        $this->client->request('GET', '/entrar');

        $last = $this->entries(EventLog::ACCESS);
        self::assertNotEmpty($last);
        self::assertNull(end($last)->getUser());
    }

    public function testSuccessfulLoginIsLogged(): void
    {
        $this->signIn(DemoData::USER_A_TEACHER);

        $entries = $this->entries(EventLog::LOGIN_SUCCESS);
        self::assertCount(1, $entries);
        self::assertSame(DemoData::USER_A_TEACHER, $entries[0]->getData());
        self::assertSame($this->getUser(DemoData::USER_A_TEACHER)->getId(), $entries[0]->getUser()?->getId());
    }

    public function testImpersonationIsLogged(): void
    {
        $this->login($this->getAdmin());

        $this->client->request('GET', '/?_switch_user=' . DemoData::USER_A_TEACHER);

        $entries = $this->entries(EventLog::SWITCH_USER);
        self::assertCount(1, $entries);
        self::assertSame(DemoData::USER_A_TEACHER, $entries[0]->getData());
        self::assertSame($this->getUser(DemoData::USER_A_TEACHER)->getId(), $entries[0]->getUser()?->getId());
    }

    public function testFailedLoginIsLoggedWithTheAttemptedUsername(): void
    {
        $this->signIn(DemoData::USER_A_TEACHER, 'clave-incorrecta');

        $entries = $this->entries(EventLog::LOGIN_ERROR);
        self::assertCount(1, $entries);
        self::assertSame(DemoData::USER_A_TEACHER, $entries[0]->getData());
        self::assertCount(0, $this->entries(EventLog::LOGIN_SUCCESS));
    }

    public function testFailedLoginForAnUnknownUserIsLogged(): void
    {
        $this->signIn('usuario.inexistente', 'clave');

        $entries = $this->entries(EventLog::LOGIN_ERROR);
        self::assertCount(1, $entries);
        self::assertSame('usuario.inexistente', $entries[0]->getData());
    }

    public function testFailedLoginWithBasicAuthHeaderDoesNotBreak(): void
    {
        $crawler = $this->client->request('GET', '/entrar');
        $this->client->request('POST', '/comprobar', [
            '_username' => DemoData::USER_A_TEACHER,
            '_password' => 'clave-incorrecta',
            '_csrf_token' => $crawler->filter('input[name=_csrf_token]')->attr('value'),
        ], [], ['REMOTE_ADDR' => $this->uniqueIp(), 'PHP_AUTH_USER' => 'otro', 'PHP_AUTH_PW' => 'x']);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    public function testVeryLongPathDoesNotBreakTheRequestLog(): void
    {
        $before = $this->logCount();

        // el listener solo actúa en rutas que existen: se usa un parámetro libre de una ruta pública
        $this->client->request('GET', '/restablecer/1/' . str_repeat('a', 600));

        // la petición debe responderse y registrarse con el dato recortado
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertGreaterThan($before, $this->logCount());
        foreach ($this->entries(EventLog::ACCESS) as $entry) {
            self::assertLessThanOrEqual(255, mb_strlen((string) $entry->getData()));
        }
    }

    public function testVeryLongUsernameDoesNotBreakTheLoginLog(): void
    {
        $this->signIn(str_repeat('u', 400), 'clave');

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertCount(1, $this->entries(EventLog::LOGIN_ERROR));
    }
}
