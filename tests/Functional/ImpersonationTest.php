<?php

namespace App\Tests\Functional;

use App\Entity\Person;
use App\Tests\Fixtures\DemoData;

/**
 * Suplantación de usuario (?_switch_user=): solo los administradores globales, y se aplican los permisos
 * de la persona suplantada, no los del administrador
 */
class ImpersonationTest extends AppWebTestCase
{
    /** Texto que la cabecera muestra para cada usuario (nombre y apellidos) */
    private const TEACHER_NAME = 'Profesor Docente Alfa';

    private function switchTo(string $username): void
    {
        $this->client->request('GET', '/?_switch_user=' . $username);
        while ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
        }
    }

    private function currentName(): string
    {
        $this->client->request('GET', '/datos');
        while ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
        }

        return trim(preg_replace('/\s+/', ' ', $this->client->getCrawler()->filter('.navbar')->text('')));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonAdministratorProvider(): iterable
    {
        yield 'administrador local' => [DemoData::USER_A_LOCAL_ADMIN];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'profesor' => [DemoData::USER_A_TEACHER];
        yield 'tutor laboral' => ['alfa.tutorlaboral'];
        yield 'alumno' => ['alfa.alumno1'];
    }

    /**
     * @dataProvider nonAdministratorProvider
     */
    public function testOnlyGlobalAdministratorsCanImpersonate(string $username): void
    {
        $this->login($this->getUser($username));
        $before = $this->currentName();

        foreach ([DemoData::USER_ADMIN, 'beta.admin', 'alfa.alumno4'] as $target) {
            $this->client->request('GET', '/?_switch_user=' . $target);
            self::assertResponseStatusCodeSame(403, "$username no debería poder suplantar a $target");
            self::assertSame($before, $this->currentName(), "$username ha cambiado de identidad");
        }
    }

    public function testAnonymousUsersCannotImpersonate(): void
    {
        $this->client->request('GET', '/?_switch_user=' . DemoData::USER_ADMIN);

        self::assertResponseRedirects();
        self::assertStringEndsWith('/entrar', $this->client->getResponse()->headers->get('Location'));
    }

    public function testAdministratorBecomesTheOtherPersonAndCanLeave(): void
    {
        $this->login($this->getAdmin());
        $adminName = $this->currentName();

        $this->switchTo(DemoData::USER_A_TEACHER);
        self::assertStringContainsString(self::TEACHER_NAME, $this->currentName(), 'No se ha suplantado a la persona');

        // vale la identidad de la persona suplantada, también para los permisos
        $this->client->request('GET', '/admin/usuarios/listar');
        self::assertResponseStatusCodeSame(403, 'Una persona suplantada no debe conservar los permisos del administrador');

        $this->switchTo('_exit');
        self::assertSame($adminName, $this->currentName());
        $this->client->request('GET', '/admin/usuarios/listar');
        self::assertResponseIsSuccessful();
    }

    public function testImpersonatingMovesTheSessionToTheOrganizationOfThePerson(): void
    {
        // el administrador está en el centro Alfa y suplanta a alguien del centro Beta
        $this->login($this->getAdmin(), true, DemoData::ORG_A_CODE);
        $this->switchTo(DemoData::USER_B_TEACHER);

        // la sesión suplantada olvida el centro del administrador y hay que elegir uno de los de la persona suplantada
        self::assertSame('/organizacion', parse_url($this->client->getRequest()->getUri(), PHP_URL_PATH));
        $page = $this->client->getCrawler();
        self::assertStringContainsString(DemoData::ORG_B_NAME, $page->text());
        self::assertStringNotContainsString(DemoData::ORG_A_NAME, $page->text(), 'Se ofrece el centro del administrador a la persona suplantada');

        $radio = $page->filter('input[type=radio]')->first();
        $form = $page->filter('form')->first()->form();
        $form[$radio->attr('name')]->select($radio->attr('value'));
        $this->client->submit($form);
        while ($this->client->getResponse()->isRedirect()) {
            $this->client->followRedirect();
        }

        // y los datos que se ven son los del centro de la persona suplantada, no los de Alfa
        $body = $this->client->getCrawler()->filter('body')->text('');
        self::assertStringContainsString(DemoData::ORG_B_NAME, $body);
        self::assertStringNotContainsString(DemoData::ORG_A_NAME, $body);
        $this->client->request('GET', '/dual');
        self::assertStringNotContainsString('Alfa', (string) $this->client->getResponse()->getContent());
    }

    public function testImpersonatingAnUnknownUserIsNotAServerError(): void
    {
        $this->login($this->getAdmin());
        $this->client->request('GET', '/?_switch_user=no.existe');

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertNotSame(200, $this->client->getResponse()->getStatusCode());
    }

    public function testDisabledUsersCannotBeImpersonated(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getRepository(Person::class)->findOneBy(['loginUsername' => 'alfa.alumno4'])->setEnabled(false);
        $em->flush();
        $em->clear();

        $this->login($this->getAdmin());
        $adminName = $this->currentName();
        $this->switchTo('alfa.alumno4');

        self::assertSame($adminName, $this->currentName(), 'Se ha podido suplantar a una cuenta deshabilitada');
    }

    public function testImpersonatedUserWithAPendingPasswordChangeIsNotForcedToChangeIt(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->getRepository(Person::class)->findOneBy(['loginUsername' => DemoData::USER_A_TEACHER])->setForcePasswordChange(true);
        $em->flush();
        $em->clear();

        $this->login($this->getAdmin());
        $this->switchTo(DemoData::USER_A_TEACHER);

        // el administrador debe poder ver lo que ve la persona sin cambiarle la contraseña
        self::assertStringContainsString(self::TEACHER_NAME, $this->currentName());
        self::assertFalse(
            str_ends_with((string) parse_url($this->client->getRequest()->getUri(), PHP_URL_PATH), '/forzar/reestablecer'),
            'La suplantación ha llevado al formulario de cambio de contraseña'
        );
    }
}
