<?php

namespace App\Tests\Functional;

use App\Entity\Person;
use App\Tests\Fixtures\DemoData;

/**
 * Selección de la organización activa tras entrar en la aplicación y al cambiar de organización.
 */
class OrganizationSelectionTest extends AppWebTestCase
{
    private function selectedOrganizationId(): mixed
    {
        return $this->client->getRequest()->getSession()->get('organization_id');
    }

    public function testUserOfASingleOrganizationEntersDirectly(): void
    {
        $this->signIn(DemoData::USER_A_TEACHER);

        self::assertSame($this->getOrganization()->getId(), $this->selectedOrganizationId());
        $this->client->request('GET', '/');
        self::assertResponseIsSuccessful();
    }

    public function testUserWithoutOrganizationInSessionIsSentToTheSelector(): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER), false);

        $this->client->request('GET', '/centro');

        self::assertResponseRedirects('/organizacion');
    }

    public function testGlobalAdministratorWithSeveralOrganizationsChoosesOne(): void
    {
        $this->signIn(DemoData::USER_ADMIN);
        $this->client->request('GET', '/centro');
        self::assertResponseRedirects('/organizacion', null, 'Sin organización elegida debe pasar por el selector');

        $crawler = $this->client->request('GET', '/organizacion');
        self::assertResponseIsSuccessful();
        $beta = $this->getOrganization(DemoData::ORG_B_CODE);
        $this->client->submit($crawler->selectButton('ENTRAR EN EL CENTRO SELECCIONADO')->form(['form[organization]' => (string) $beta->getId()]));

        self::assertResponseRedirects();
        $this->client->request('GET', '/centro');
        self::assertResponseIsSuccessful();
        self::assertStringContainsString(DemoData::ORG_B_NAME, (string) $this->client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}> usuario y código de la organización ajena
     */
    public static function foreignOrganizationProvider(): iterable
    {
        yield 'profesorado de Alfa' => [DemoData::USER_A_TEACHER, DemoData::ORG_B_CODE];
        yield 'administrador local de Alfa' => [DemoData::USER_A_LOCAL_ADMIN, DemoData::ORG_B_CODE];
        yield 'administrador local de Beta' => [DemoData::USER_B_LOCAL_ADMIN, DemoData::ORG_A_CODE];
        yield 'alumnado de Alfa' => ['alfa.alumno1', DemoData::ORG_B_CODE];
    }

    /**
     * @dataProvider foreignOrganizationProvider
     */
    public function testUserCannotSwitchToAnOrganizationTheyDoNotBelongTo(string $username, string $foreignCode): void
    {
        $this->signIn($username);
        $own = $this->selectedOrganizationId();
        $person = $this->getUser($username);
        $defaultBefore = $person->getDefaultOrganization()?->getId();

        $this->client->request('GET', '/organizacion/' . $this->getOrganization($foreignCode)->getId());

        self::assertResponseStatusCodeSame(403);
        self::assertSame($own, $this->selectedOrganizationId());
        $reloaded = static::getContainer()->get('doctrine')->getManager()->getRepository(Person::class)->find($person->getId());
        self::assertSame($defaultBefore, $reloaded->getDefaultOrganization()?->getId());
    }

    public function testGlobalAdministratorCanSwitchToAnyOrganization(): void
    {
        // con una organización ya activa, como al usar el menú de cambio de centro
        $this->login($this->getAdmin());
        $beta = $this->getOrganization(DemoData::ORG_B_CODE);

        $this->client->request('GET', '/organizacion/' . $beta->getId());

        self::assertResponseRedirects();
        $this->client->request('GET', '/centro');
        self::assertStringContainsString(DemoData::ORG_B_NAME, (string) $this->client->getResponse()->getContent());
    }

    public function testSwitchingToAnUnknownOrganizationChangesNothing(): void
    {
        $this->signIn(DemoData::USER_A_TEACHER);
        $own = $this->selectedOrganizationId();

        // un identificador desconocido muestra el selector, sin cambiar la organización activa
        $this->client->request('GET', '/organizacion/999999');

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($own, $this->selectedOrganizationId());
    }

    public function testAnonymousUserCannotSwitchOrganization(): void
    {
        $this->client->request('GET', '/organizacion/' . $this->getOrganization()->getId());

        self::assertResponseRedirects();
        self::assertStringContainsString('/entrar', (string) $this->client->getResponse()->headers->get('Location'));
    }
}
