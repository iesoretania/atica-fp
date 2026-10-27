<?php

namespace App\Tests\Functional;

use App\Repository\OrganizationRepository;
use App\Tests\Fixtures\DemoData;

/**
 * Un administrador local pertenece a la organización que administra aunque no sea profesorado ni alumnado
 * (en las fixtures, "alfa.admin" y "beta.admin" solo tienen el rol de administrador local).
 */
class LocalAdministratorMembershipTest extends AppWebTestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function administratorProvider(): iterable
    {
        yield 'administrador local de Alfa' => [DemoData::USER_A_LOCAL_ADMIN, DemoData::ORG_A_NAME];
        yield 'administrador local de Beta' => [DemoData::USER_B_LOCAL_ADMIN, DemoData::ORG_B_NAME];
    }

    /**
     * @dataProvider administratorProvider
     */
    public function testLocalAdministratorCanSignInAndLandsInTheirOrganization(string $username, string $organizationName): void
    {
        $crawler = $this->client->request('GET', '/entrar');
        $this->client->request('POST', '/comprobar', [
            '_username' => $username,
            '_password' => DemoData::PASSWORD,
            '_csrf_token' => $crawler->filter('input[name=_csrf_token]')->attr('value'),
        ], [], ['REMOTE_ADDR' => $this->uniqueIp()]);

        // se sigue la cadena de redirecciones hasta la página final
        for ($i = 0; $i < 4 && $this->client->getResponse()->isRedirect(); $i++) {
            $this->client->followRedirect();
        }

        $content = (string) $this->client->getResponse()->getContent();
        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('ningún centro', $content, 'La aplicación dice que no pertenece a ningún centro');
        self::assertStringContainsString($organizationName, $content);
        self::assertStringNotContainsString('/organizacion', parse_url($this->client->getRequest()->getUri(), PHP_URL_PATH));
    }

    /**
     * @dataProvider administratorProvider
     */
    public function testOrganizationsOfALocalAdministratorAreOnlyTheirOwn(string $username, string $organizationName): void
    {
        $organizations = static::getContainer()->get(OrganizationRepository::class)
            ->getMembershipByPerson($this->getUser($username));

        self::assertSame([$organizationName], array_map(fn ($organization): string => $organization->getName(), $organizations));
    }

    public function testLocalAdministratorCanManageTheirOrganizationAfterSigningIn(): void
    {
        $crawler = $this->client->request('GET', '/entrar');
        $this->client->request('POST', '/comprobar', [
            '_username' => DemoData::USER_A_LOCAL_ADMIN,
            '_password' => DemoData::PASSWORD,
            '_csrf_token' => $crawler->filter('input[name=_csrf_token]')->attr('value'),
        ], [], ['REMOTE_ADDR' => $this->uniqueIp()]);
        for ($i = 0; $i < 4 && $this->client->getResponse()->isRedirect(); $i++) {
            $this->client->followRedirect();
        }

        // la gestión del centro funciona con la sesión real, sin que el test fije la organización a mano
        $this->client->request('GET', static::getContainer()->get('router')->generate('organization_group_list'));
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('1º DAM A', $this->client->getResponse()->getContent());

        // y la administración global sigue vedada
        $this->client->request('GET', static::getContainer()->get('router')->generate('admin_user_list'));
        self::assertResponseStatusCodeSame(403);
    }
}
