<?php

namespace App\Tests\Functional;

use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Mime\Email;

/**
 * Datos personales: cambio de la dirección de correo (con confirmación por enlace) y de la contraseña propia
 */
class PersonalDataTest extends AppWebTestCase
{
    private const NEW_EMAIL = 'nueva.direccion@example.com';
    private const NEW_PASSWORD = 'ClaveNueva-2027';

    /**
     * Correos enviados: el registro se vacía al empezar cada petición, así que se recogen tras cada envío
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
     * @param array<string, string> $values
     */
    private function submitPersonalData(array $values, string $button = 'person[submit]'): Crawler
    {
        $crawler = $this->client->request('GET', '/datos');
        $form = $crawler->filter('form')->reduce(fn (Crawler $f): bool => $f->filter('input[name="person[_token]"]')->count() > 0)
            ->first()->form();
        $crawler = $this->client->submit($form, $values, [], $button === 'person[submit]' ? [] : []);
        foreach (self::getMailerMessages() as $message) {
            $this->mails[] = $message;
        }

        return $crawler;
    }

    /**
     * Pulsa el botón indicado del formulario de datos personales
     *
     * @param array<string, string> $values
     */
    private function press(string $buttonName, array $values): void
    {
        $crawler = $this->client->request('GET', '/datos');
        $form = $crawler->filter(sprintf('button[name="%s"]', $buttonName))->form($values);
        $this->client->submit($form);
        foreach (self::getMailerMessages() as $message) {
            $this->mails[] = $message;
        }
    }

    /**
     * @return array{int, string}
     */
    private function emailLink(): array
    {
        self::assertNotEmpty($this->mails, 'No se ha enviado ningún correo');
        self::assertSame(1, preg_match('#/restablecer/correo/(\d+)/([0-9a-f]{32})#', (string) end($this->mails)->getTextBody(), $matches));

        return [(int) $matches[1], $matches[2]];
    }

    // --- Dirección de correo ---------------------------------------------------------------------

    public function testEmailChangeNeedsConfirmationAndTheLinkGoesToTheNewAddress(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $oldEmail = $this->person()->getEmailAddress();

        $this->press('person[submit]', ['person[emailAddress]' => self::NEW_EMAIL]);

        // hasta confirmarlo, la dirección no cambia
        $person = $this->person();
        self::assertSame($oldEmail, $person->getEmailAddress(), 'La dirección ha cambiado sin confirmación');
        self::assertSame(self::NEW_EMAIL, $person->getTokenType(), 'La dirección pendiente de confirmar no está guardada');
        self::assertNotNull($person->getTokenExpiration());

        // el enlace de confirmación se envía a la dirección NUEVA (es la forma de comprobar que es del usuario)
        self::assertCount(1, $this->mails);
        self::assertEmailAddressContains($this->mails[0], 'To', self::NEW_EMAIL);
        self::assertCount(1, $this->mails[0]->getTo());
        [$id, $token] = $this->emailLink();
        self::assertSame($person->getId(), $id);
        self::assertSame($token, $person->getToken());
    }

    public function testConfirmingTheLinkChangesTheAddressOnceOnly(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $this->press('person[submit]', ['person[emailAddress]' => self::NEW_EMAIL]);
        [$id, $token] = $this->emailLink();

        $crawler = $this->client->request('GET', "/restablecer/correo/$id/$token");
        self::assertResponseIsSuccessful();
        // abrir el enlace no cambia nada: hay que confirmarlo
        self::assertNotSame(self::NEW_EMAIL, $this->person()->getEmailAddress());

        $this->client->submit($crawler->filter('form')->first()->form());
        self::assertResponseRedirects();
        $person = $this->person();
        self::assertSame(self::NEW_EMAIL, $person->getEmailAddress());
        self::assertNull($person->getToken());
        self::assertNull($person->getTokenType());
        self::assertNull($person->getTokenExpiration());

        // el enlace no vale una segunda vez
        $this->client->request('GET', "/restablecer/correo/$id/$token");
        self::assertResponseRedirects();
        self::assertStringEndsWith('/entrar', $this->client->getResponse()->headers->get('Location'));
    }

    public function testEmailAlreadyUsedByAnotherPersonIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $oldEmail = $this->person()->getEmailAddress();
        $taken = $this->person(DemoData::USER_B_TEACHER)->getEmailAddress();

        $this->press('person[submit]', ['person[emailAddress]' => $taken]);

        self::assertCount(0, $this->mails, 'No debe enviarse un enlace para una dirección ya en uso');
        $person = $this->person();
        self::assertSame($oldEmail, $person->getEmailAddress());
        self::assertNull($person->getTokenType());
    }

    public function testInvalidEmailIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $this->press('person[submit]', ['person[emailAddress]' => 'esto-no-es-un-correo']);

        self::assertCount(0, $this->mails);
        self::assertNull($this->person()->getTokenType());
    }

    public function testExpiredEmailLinkIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $this->press('person[submit]', ['person[emailAddress]' => self::NEW_EMAIL]);
        [$id, $token] = $this->emailLink();

        $em = $this->em();
        $em->getRepository(Person::class)->find($id)->setTokenExpiration(new \DateTime('-1 minute'));
        $em->flush();

        $this->client->request('GET', "/restablecer/correo/$id/$token");
        self::assertResponseRedirects();
        self::assertNotSame(self::NEW_EMAIL, $this->person()->getEmailAddress());
    }

    public function testPasswordResetLinkCannotConfirmAnEmailChange(): void
    {
        $em = $this->em();
        $person = $em->getRepository(Person::class)->findOneBy(['loginUsername' => DemoData::USER_A_TEACHER]);
        $person->setToken(str_repeat('c', 32))->setTokenType('password')->setTokenExpiration(new \DateTime('+30 minutes'));
        $em->flush();

        $this->client->request('GET', '/restablecer/correo/' . $person->getId() . '/' . str_repeat('c', 32));

        self::assertResponseRedirects();
        self::assertStringEndsWith('/entrar', $this->client->getResponse()->headers->get('Location'));
    }

    public function testGlobalAdministratorChangesTheirAddressImmediately(): void
    {
        $this->login($this->getAdmin());
        $this->press('person[submit]', ['person[emailAddress]' => self::NEW_EMAIL]);

        self::assertCount(0, $this->mails);
        self::assertSame(self::NEW_EMAIL, $this->person(DemoData::USER_ADMIN)->getEmailAddress());
    }

    // --- Contraseña propia -----------------------------------------------------------------------

    public function testPasswordIsChangedWithTheCurrentOne(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $this->press('person[changePassword]', [
            'person[oldPassword]' => DemoData::PASSWORD,
            'person[newPassword][first]' => self::NEW_PASSWORD,
            'person[newPassword][second]' => self::NEW_PASSWORD,
        ]);

        self::assertResponseRedirects();
        $hasher = static::getContainer()->get('security.user_password_hasher');
        $person = $this->person();
        self::assertTrue($hasher->isPasswordValid($person, self::NEW_PASSWORD));
        self::assertFalse($hasher->isPasswordValid($person, DemoData::PASSWORD));
    }

    /**
     * @return iterable<string, array{string, string, string}> contraseña actual, nueva y repetición
     */
    public static function rejectedPasswordProvider(): iterable
    {
        yield 'contraseña actual incorrecta' => ['no-es-esta', self::NEW_PASSWORD, self::NEW_PASSWORD];
        yield 'contraseña actual vacía' => ['', self::NEW_PASSWORD, self::NEW_PASSWORD];
        yield 'contraseña nueva demasiado corta' => [DemoData::PASSWORD, 'corta', 'corta'];
        yield 'las contraseñas nuevas no coinciden' => [DemoData::PASSWORD, self::NEW_PASSWORD, 'Distinta-2027'];
    }

    /**
     * @dataProvider rejectedPasswordProvider
     */
    public function testPasswordIsNotChangedWithInvalidData(string $current, string $new, string $repeat): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $this->press('person[changePassword]', [
            'person[oldPassword]' => $current,
            'person[newPassword][first]' => $new,
            'person[newPassword][second]' => $repeat,
        ]);

        $hasher = static::getContainer()->get('security.user_password_hasher');
        $person = $this->person();
        self::assertTrue($hasher->isPasswordValid($person, DemoData::PASSWORD), 'La contraseña ha cambiado con datos no válidos');
        self::assertFalse($hasher->isPasswordValid($person, $new), 'Se ha guardado la contraseña nueva');
    }
}
