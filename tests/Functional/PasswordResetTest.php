<?php

namespace App\Tests\Functional;

use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mime\Email;

/**
 * Restablecimiento de la contraseña por correo electrónico: solicitud, enlace, caducidad, uso único y
 * que no se pueda averiguar qué correos tienen cuenta.
 */
class PasswordResetTest extends AppWebTestCase
{
    private const NEW_PASSWORD = 'NuevaClave-2027';

    /**
     * Correos enviados durante el test. El registro de correos de Symfony se vacía al empezar cada petición
     * (también al seguir una redirección), por lo que se recogen justo después de cada envío.
     *
     * @var list<Email>
     */
    private array $mails = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->client->disableReboot();
        $this->mails = [];
    }

    private function em(): \Doctrine\ORM\EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function person(string $username = DemoData::USER_A_TEACHER): Person
    {
        return $this->em()->getRepository(Person::class)->findOneBy(['loginUsername' => $username]);
    }

    /**
     * Envía el formulario de "¿Olvidó la contraseña?" y devuelve la página resultante tras seguir las redirecciones
     */
    private function requestReset(string $email): Crawler
    {
        $crawler = $this->client->request('GET', '/restablecer');
        $form = $crawler->filter('form')->first()->form();
        $field = $crawler->filter('input[type=email], input[name*=email]')->first()->attr('name');
        $form[$field] = $email;
        $this->client->submit($form, [], ['REMOTE_ADDR' => $this->uniqueIp()]);
        foreach (self::getMailerMessages() as $message) {
            $this->mails[] = $message;
        }
        while ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
        }

        return $this->client->getCrawler();
    }

    /**
     * Texto visible de los mensajes de la página (avisos de éxito, error o información)
     */
    private function messages(Crawler $crawler): string
    {
        return trim(preg_replace('/\s+/', ' ', implode(' | ', $crawler->filter('.alert')->each(fn (Crawler $n): string => $n->text()))));
    }

    /**
     * @return array{int, string} identificador de la persona y token del último enlace enviado
     */
    private function lastLink(): array
    {
        self::assertNotEmpty($this->mails, 'No se ha enviado ningún correo');
        $email = end($this->mails);
        self::assertInstanceOf(Email::class, $email);
        self::assertSame(1, preg_match('#/restablecer/(\d+)/([0-9a-f]{32})#', (string) $email->getTextBody(), $matches), 'El correo no contiene el enlace');

        return [(int) $matches[1], $matches[2]];
    }

    private function setNewPassword(int $id, string $token, string $password, ?string $confirmation = null): Crawler
    {
        $crawler = $this->client->request('GET', "/restablecer/$id/$token");
        if ($crawler->filter('form')->count() === 0) {
            // el enlace no es válido: la aplicación redirige y no hay formulario que enviar
            return $crawler;
        }
        $form = $crawler->filter('form')->first()->form();
        $names = $crawler->filter('input[type=password]')->each(fn (Crawler $n): string => $n->attr('name'));
        $form[$names[0]] = $password;
        $form[$names[1]] = $confirmation ?? $password;

        return $this->client->submit($form);
    }

    public function testRequestSendsOneEmailWithAWorkingLinkToTheRightPerson(): void
    {
        $person = $this->person();
        $this->requestReset($person->getEmailAddress());

        self::assertCount(1, $this->mails);
        $email = $this->mails[0];
        self::assertEmailAddressContains($email, 'To', $person->getEmailAddress());
        self::assertCount(1, $email->getTo(), 'El correo solo debe ir a la persona interesada');
        self::assertEmailAddressContains($email, 'From', 'test@example.com');
        self::assertStringNotContainsString(DemoData::PASSWORD, (string) $email->getTextBody());

        [$id, $token] = $this->lastLink();
        self::assertSame($person->getId(), $id);
        $reloaded = $this->person();
        self::assertSame($token, $reloaded->getToken());
        self::assertSame('password', $reloaded->getTokenType());
        $minutes = (int) static::getContainer()->getParameter('password_reset.expire');
        self::assertEqualsWithDelta(
            time() + $minutes * 60,
            $reloaded->getTokenExpiration()->getTimestamp(),
            120,
            'La caducidad del enlace no es la configurada'
        );

        $this->client->request('GET', "/restablecer/$id/$token");
        self::assertResponseIsSuccessful();
    }

    public function testRequestsForKnownAndUnknownEmailsAreIndistinguishable(): void
    {
        $known = $this->requestReset($this->person()->getEmailAddress());
        $knownMessages = $this->messages($known);
        $knownPath = parse_url($this->client->getRequest()->getUri(), PHP_URL_PATH);

        $unknown = $this->requestReset('nadie.registrado@example.com');
        $unknownMessages = $this->messages($unknown);
        $unknownPath = parse_url($this->client->getRequest()->getUri(), PHP_URL_PATH);

        // el aviso incluye la dirección escrita: se compara sin ella
        $normalize = static fn (string $text): string => preg_replace('/\S+@\S+/', '<correo>', $text);
        self::assertSame($knownPath, $unknownPath, 'La respuesta lleva a páginas distintas según exista o no la cuenta');
        self::assertSame($normalize($knownMessages), $normalize($unknownMessages), 'El mensaje revela si el correo tiene cuenta');
        // la pantalla de entrada también debe ser igual: el campo de usuario no puede rellenarse solo si la cuenta existe
        self::assertSame(
            $normalize((string) $known->filter('input[name=_username]')->attr('value')),
            $normalize((string) $unknown->filter('input[name=_username]')->attr('value')),
            'El campo de usuario de la pantalla de entrada revela si el correo tiene cuenta'
        );
        self::assertCount(1, $this->mails, 'Solo debe enviarse correo cuando la cuenta existe');
    }

    public function testRepeatedRequestForTheSameAccountDoesNotRevealThatALinkIsPending(): void
    {
        $email = $this->person()->getEmailAddress();
        $first = $this->messages($this->requestReset($email));
        $second = $this->messages($this->requestReset($email));

        self::assertSame($first, $second, 'La segunda petición revela que ya hay un enlace pendiente (y, con ello, que la cuenta existe)');
        self::assertCount(1, $this->mails, 'No debe enviarse un segundo correo mientras el enlace siga vigente');
    }

    public function testPasswordIsChangedAndTheLinkCanOnlyBeUsedOnce(): void
    {
        $this->requestReset($this->person()->getEmailAddress());
        [$id, $token] = $this->lastLink();

        $this->setNewPassword($id, $token, self::NEW_PASSWORD);
        self::assertResponseRedirects();

        $person = $this->person();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        self::assertTrue($hasher->isPasswordValid($person, self::NEW_PASSWORD));
        self::assertFalse($hasher->isPasswordValid($person, DemoData::PASSWORD), 'La contraseña anterior sigue siendo válida');
        self::assertNull($person->getToken());
        self::assertNull($person->getTokenExpiration());
        self::assertNull($person->getTokenType());

        // el enlace ya no sirve, ni para ver el formulario ni para enviarlo
        $this->client->request('GET', "/restablecer/$id/$token");
        self::assertResponseRedirects();
        self::assertStringEndsWith('/entrar', $this->client->getResponse()->headers->get('Location'));
        $this->setNewPassword($id, $token, 'OtraClave-2027');
        self::assertFalse($hasher->isPasswordValid($this->person(), 'OtraClave-2027'), 'El enlace ha podido usarse dos veces');
    }

    public function testNewPasswordWorksForSigningInAndOldOneDoesNot(): void
    {
        $this->requestReset($this->person()->getEmailAddress());
        [$id, $token] = $this->lastLink();
        $this->setNewPassword($id, $token, self::NEW_PASSWORD);

        // restablecer la contraseña no inicia sesión por sí solo
        $this->client->request('GET', static::getContainer()->get('router')->generate('personal_data'));
        self::assertResponseRedirects();

        $this->signIn(DemoData::USER_A_TEACHER, DemoData::PASSWORD);
        $this->client->request('GET', static::getContainer()->get('router')->generate('personal_data'));
        self::assertResponseRedirects('/entrar', null, 'La contraseña anterior todavía permite entrar');

        $this->signIn(DemoData::USER_A_TEACHER, self::NEW_PASSWORD);
        $this->client->request('GET', static::getContainer()->get('router')->generate('personal_data'));
        self::assertResponseIsSuccessful();
    }

    public function testWeakOrMismatchedPasswordsAreRejectedAndTheLinkSurvives(): void
    {
        $this->requestReset($this->person()->getEmailAddress());
        [$id, $token] = $this->lastLink();
        $hasher = static::getContainer()->get('security.user_password_hasher');

        $this->setNewPassword($id, $token, 'corta');
        self::assertResponseStatusCodeSame(200);
        $this->setNewPassword($id, $token, self::NEW_PASSWORD, 'Distinta-2027');
        self::assertResponseStatusCodeSame(200);

        $person = $this->person();
        self::assertTrue($hasher->isPasswordValid($person, DemoData::PASSWORD), 'La contraseña ha cambiado con datos no válidos');
        self::assertSame($token, $person->getToken(), 'Un intento fallido no debe consumir el enlace');
    }

    public function testExpiredLinkIsRejected(): void
    {
        $this->requestReset($this->person()->getEmailAddress());
        [$id, $token] = $this->lastLink();

        $em = $this->em();
        $em->getRepository(Person::class)->find($id)->setTokenExpiration(new \DateTime('-1 minute'));
        $em->flush();

        $this->client->request('GET', "/restablecer/$id/$token");
        self::assertResponseRedirects();
        $this->setNewPassword($id, $token, self::NEW_PASSWORD);
        self::assertFalse(
            static::getContainer()->get('security.user_password_hasher')->isPasswordValid($this->person(), self::NEW_PASSWORD),
            'Un enlace caducado ha cambiado la contraseña'
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidLinkProvider(): iterable
    {
        yield 'token incorrecto' => ['token incorrecto'];
        yield 'token de otra persona' => ['token de otra persona'];
        yield 'token de cambio de correo' => ['token de cambio de correo'];
        yield 'sin caducidad' => ['sin caducidad'];
    }

    /**
     * @dataProvider invalidLinkProvider
     */
    public function testInvalidLinksAreRejected(string $case): void
    {
        $victim = $this->person();
        $other = $this->person(DemoData::USER_B_TEACHER);
        $em = $this->em();
        $other = $em->getRepository(Person::class)->find($other->getId());
        $victimEntity = $em->getRepository(Person::class)->find($victim->getId());
        $valid = str_repeat('a', 32);

        // la persona "otra" tiene un enlace válido de restablecimiento
        $other->setToken($valid)->setTokenType('password')->setTokenExpiration(new \DateTime('+30 minutes'));
        $url = match ($case) {
            'token incorrecto' => '/restablecer/' . $victim->getId() . '/' . str_repeat('b', 32),
            'token de otra persona' => '/restablecer/' . $victim->getId() . '/' . $valid,
            'token de cambio de correo' => (function () use ($victimEntity, $valid): string {
                // este token sirve para confirmar una dirección nueva, no para cambiar la contraseña
                $victimEntity->setToken($valid . '')->setTokenType('nueva@example.com')->setTokenExpiration(new \DateTime('+30 minutes'));
                return '/restablecer/' . $victimEntity->getId() . '/' . $valid;
            })(),
            'sin caducidad' => (function () use ($victimEntity, $valid): string {
                $victimEntity->setToken($valid)->setTokenType('password')->setTokenExpiration(null);
                return '/restablecer/' . $victimEntity->getId() . '/' . $valid;
            })(),
        };
        $em->flush();

        $this->client->request('GET', $url);
        self::assertResponseRedirects(null, null, "El enlace ($case) debería rechazarse");
        self::assertStringEndsWith('/entrar', $this->client->getResponse()->headers->get('Location'));
    }

    public function testChangingThePasswordSignsOutOtherSessions(): void
    {
        $this->signIn(DemoData::USER_A_TEACHER);
        $path = static::getContainer()->get('router')->generate('personal_data');
        $this->client->request('GET', $path);
        self::assertResponseIsSuccessful();

        // otra persona (o el propio usuario desde otro equipo) restablece la contraseña
        $em = $this->em();
        $person = $em->getRepository(Person::class)->findOneBy(['loginUsername' => DemoData::USER_A_TEACHER]);
        $person->setPassword(static::getContainer()->get('security.user_password_hasher')->hashPassword($person, self::NEW_PASSWORD));
        $em->flush();

        $this->client->request('GET', $path);
        self::assertResponseRedirects('/entrar', null, 'La sesión abierta con la contraseña anterior sigue activa');
    }

    public function testRequestsAreLimitedPerAddress(): void
    {
        $ip = $this->uniqueIp();
        $statuses = [];
        for ($i = 0; $i < 25; $i++) {
            $crawler = $this->client->request('GET', '/restablecer');
            $form = $crawler->filter('form')->first()->form();
            $form[$crawler->filter('input[type=email], input[name*=email]')->first()->attr('name')] = "inexistente$i@example.com";
            $this->client->submit($form, [], ['REMOTE_ADDR' => $ip]);
            $statuses[] = $this->client->getResponse()->getStatusCode();
        }

        self::assertContains(429, $statuses, 'Se pueden hacer peticiones de restablecimiento sin límite desde la misma dirección');
    }
}
