<?php

namespace App\Tests\Functional;

use App\Entity\Edu\Department;
use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Cambio obligatorio de contraseña: mientras esté pendiente, el usuario no puede usar nada más de la aplicación
 */
class ForcePasswordChangeTest extends AppWebTestCase
{
    private const NEW_PASSWORD = 'ClaveNueva-2027';
    private const FORCE_PATH = '/forzar/reestablecer';

    private function force(string $username = DemoData::USER_A_TEACHER): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getRepository(Person::class)->findOneBy(['loginUsername' => $username])->setForcePasswordChange(true);
        $em->flush();
        $em->clear();
    }

    private function path(): string
    {
        return (string) parse_url($this->client->getRequest()->getUri(), PHP_URL_PATH);
    }

    private function person(): Person
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em->getRepository(Person::class)->findOneBy(['loginUsername' => DemoData::USER_A_TEACHER]);
    }

    /**
     * Rellena y envía el formulario de cambio obligatorio
     */
    private function changePassword(string $current, string $new, ?string $repeat = null): Crawler
    {
        $crawler = $this->client->request('GET', self::FORCE_PATH);
        $form = $crawler->filter('input[type=password]')->first()->ancestors()->filter('form')->first()->form();
        $names = $crawler->filter('input[type=password]')->each(fn (Crawler $n): string => $n->attr('name'));
        $form[$names[0]] = $current;
        $form[$names[1]] = $new;
        $form[$names[2]] = $repeat ?? $new;

        return $this->client->submit($form);
    }

    public function testForcedUserIsSentToChangeThePasswordRightAfterSigningIn(): void
    {
        $this->force();
        $this->signIn(DemoData::USER_A_TEACHER);

        self::assertSame(self::FORCE_PATH, $this->path());
        self::assertResponseIsSuccessful();
    }

    public function testForcedUserCannotUseAnyOtherPage(): void
    {
        $this->force();
        $this->signIn(DemoData::USER_A_TEACHER);

        foreach (['/', '/datos', '/dual', '/fct', '/formacion', '/api/travel_route/query'] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseRedirects(null, null, "$url debería exigir antes cambiar la contraseña");
            self::assertStringEndsWith(self::FORCE_PATH, $this->client->getResponse()->headers->get('Location'), $url);
        }
    }

    public function testForcedUserCannotWriteAnything(): void
    {
        $this->force();
        $this->signIn(DemoData::USER_A_TEACHER);
        $em = static::getContainer()->get('doctrine')->getManager();
        $before = $em->getRepository(Department::class)->count([]);

        // ni siquiera con una petición hecha a mano
        $this->client->request('POST', static::getContainer()->get('router')->generate('organization_department_new'), [
            'department' => ['name' => 'Creado sin cambiar la contraseña'],
        ]);

        self::assertResponseRedirects();
        self::assertSame($before, $em->getRepository(Department::class)->count([]));
    }

    public function testForcedUserCanStillSignOut(): void
    {
        $this->force();
        $this->signIn(DemoData::USER_A_TEACHER);

        $this->client->request('GET', '/salir');
        self::assertResponseRedirects();
        $this->client->request('GET', '/datos');
        self::assertStringEndsWith('/entrar', $this->client->getResponse()->headers->get('Location'));
    }

    public function testPasswordIsChangedAndTheUserReturnsToTheRequestedPage(): void
    {
        $this->force();
        $this->signIn(DemoData::USER_A_TEACHER);
        $this->client->request('GET', '/datos');           // se guarda como página a la que volver
        $this->changePassword(DemoData::PASSWORD, self::NEW_PASSWORD);

        self::assertResponseRedirects();
        self::assertSame('/datos', parse_url($this->client->getResponse()->headers->get('Location'), PHP_URL_PATH));

        $person = $this->person();
        self::assertFalse($person->isForcePasswordChange());
        self::assertTrue(static::getContainer()->get('security.user_password_hasher')->isPasswordValid($person, self::NEW_PASSWORD));

        // y ya puede usar la aplicación
        $this->client->request('GET', '/datos');
        self::assertResponseIsSuccessful();
        // la página de cambio ya no se muestra
        $this->client->request('GET', self::FORCE_PATH);
        self::assertResponseRedirects();
    }

    /**
     * @return iterable<string, array{string, string, string}> contraseña actual, nueva y repetición
     */
    public static function rejectedChangeProvider(): iterable
    {
        yield 'la contraseña actual no es la real' => ['no-es-esta', self::NEW_PASSWORD, self::NEW_PASSWORD];
        yield 'la nueva es igual que la actual' => [DemoData::PASSWORD, DemoData::PASSWORD, DemoData::PASSWORD];
        yield 'la nueva es demasiado corta' => [DemoData::PASSWORD, 'corta', 'corta'];
        yield 'la repetición no coincide' => [DemoData::PASSWORD, self::NEW_PASSWORD, 'Distinta-2027'];
    }

    /**
     * @dataProvider rejectedChangeProvider
     */
    public function testInvalidChangesAreRejectedAndTheUserStaysBlocked(string $current, string $new, string $repeat): void
    {
        $this->force();
        $this->signIn(DemoData::USER_A_TEACHER);
        $this->changePassword($current, $new, $repeat);

        $person = $this->person();
        self::assertTrue($person->isForcePasswordChange(), 'El cambio obligatorio se ha dado por cumplido con datos no válidos');
        self::assertTrue(static::getContainer()->get('security.user_password_hasher')->isPasswordValid($person, DemoData::PASSWORD));

        $this->client->request('GET', '/datos');
        self::assertStringEndsWith(self::FORCE_PATH, $this->client->getResponse()->headers->get('Location'));
    }

    public function testUsersWhoAreNotForcedAreNotSentToThePage(): void
    {
        $this->signIn(DemoData::USER_A_TEACHER);
        $this->client->request('GET', self::FORCE_PATH);

        self::assertResponseRedirects();
        self::assertSame('/', parse_url($this->client->getResponse()->headers->get('Location'), PHP_URL_PATH));
    }
}
