<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;

/**
 * Listado de seguimiento de FCT y su API: qué convenios ve cada rol y qué datos personales se devuelven.
 *  - convenio 1: alumno1 y alumno2 (tutor docente y de grupo "tutor", tutor laboral "tutorlaboral")
 *  - convenio 2: alumno3 (tutor docente "profesor", tutor laboral "tutorlaboral2")
 */
class WptTrackingListTest extends AppWebTestCase
{
    private const API = '/fct/seguimiento/api/v2/acuerdo/listar';

    /**
     * @return array<string, mixed>
     */
    private function api(): array
    {
        $this->client->request('GET', self::API);
        self::assertResponseIsSuccessful();

        return json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string> nombres de usuario del alumnado de los convenios devueltos
     */
    private function students(array $response): array
    {
        return array_values(array_unique(array_map(
            static fn(array $item): string => $item['agreement']['studentEnrollment']['person']['firstName'],
            $response['agreements']
        )));
    }

    /**
     * @return iterable<string, array{string, int}> usuario y número de convenios que ve
     */
    public static function visibilityProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin', 3];
        yield 'jefe de departamento' => ['alfa.jefe', 3];
        yield 'tutor docente y de grupo' => ['alfa.tutor', 3];
        yield 'tutor docente del convenio 2' => ['alfa.profesor', 1];
        yield 'tutor laboral del convenio 1' => ['alfa.tutorlaboral', 2];
        yield 'tutor laboral del convenio 2' => ['alfa.tutorlaboral2', 1];
        yield 'alumno del convenio 1' => ['alfa.alumno1', 1];
        yield 'alumno del convenio 2' => ['alfa.alumno3', 1];
    }

    /**
     * @dataProvider visibilityProvider
     */
    public function testEachRoleSeesOnlyItsAgreements(string $username, int $expected): void
    {
        $this->login($this->getUser($username));

        $response = $this->api();

        self::assertCount($expected, $response['agreements'], $username);
        foreach ($response['agreements'] as $item) {
            self::assertSame(['agreement', 'horas_totales', 'horas_bloqueadas', 'jornadas_sin_asistir', 'faltas_justificadas'], array_keys($item));
        }
    }

    public function testStudentSeesOnlyTheirOwnEnrollment(): void
    {
        $this->login($this->getUser('alfa.alumno3'));

        self::assertSame(['Alumno 3'], $this->students($this->api()));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function everyRoleProvider(): iterable
    {
        foreach (['admin', 'jefe', 'tutor', 'profesor', 'tutorlaboral', 'tutorlaboral2', 'alumno1', 'alumno3'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider everyRoleProvider
     */
    public function testApiNeverExposesCredentialsOrResetTokens(string $username): void
    {
        $this->login($this->getUser($username));

        $this->client->request('GET', self::API);
        $content = (string) $this->client->getResponse()->getContent();

        // ni la contraseña cifrada ni los datos del restablecimiento ni otros datos internos de las personas
        foreach (['"password"', '"token"', '"tokenType"', '"tokenExpiration"', '"blockedUntil"', '"globalAdministrator"',
                     '"forcePasswordChange"', '"externalCheck"', '"uniqueIdentifier"', '"loginUsername"', '$2y$'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $content, "$username: $forbidden");
        }
        // pero sí los nombres, para identificar a las personas
        self::assertStringContainsString('"firstName"', $content);
    }

    public function testApiExposesNoPendingResetTokenOfTheWorkTutor(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $workTutor = $this->getUser('alfa.tutorlaboral');
        $workTutor->setToken('token-de-restablecimiento-secreto')->setTokenType('password')
            ->setTokenExpiration(new \DateTime('+1 hour'));
        $em->flush();
        $em->clear();
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('GET', self::API);

        self::assertStringNotContainsString('token-de-restablecimiento-secreto', (string) $this->client->getResponse()->getContent());
    }

    public function testApiOnlyListsTheCurrentOrganization(): void
    {
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        $this->client->request('GET', self::API);
        $content = (string) $this->client->getResponse()->getContent();

        self::assertStringNotContainsString('Alfa', $content);
        self::assertStringContainsString('Beta', $content);
    }

    public function testPeopleWithoutFctRoleCannotUseTheApiOrTheList(): void
    {
        $this->login($this->getUser('alfa.gestorffe'));

        $this->client->request('GET', self::API);
        $api = $this->client->getResponse()->getStatusCode();
        $this->client->request('GET', '/fct/seguimiento/acuerdo/listar/' . $this->getOrganization()->getCurrentAcademicYear()->getId());

        // quien no interviene en FCT no ve convenios
        self::assertContains($api, [200, 403]);
        if ($api === 200) {
            self::assertCount(0, json_decode((string) $this->client->getResponse()->getContent() ?: '[]', true)['agreements'] ?? []);
        }
    }

    public function testAnonymousUsersAreSentToTheLogin(): void
    {
        $this->client->request('GET', self::API);

        self::assertResponseRedirects();
    }

    // --- Listado en pantalla ----------------------------------------------------------------------------

    public function testListShowsAgreementsAndFiltersBySearch(): void
    {
        $year = $this->getOrganization()->getCurrentAcademicYear()->getId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', "/fct/seguimiento/acuerdo/listar/$year");
        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Alumno 1', $content);
        self::assertStringContainsString('Alumno 3', $content);

        $this->client->request('GET', "/fct/seguimiento/acuerdo/listar/$year", ['q' => 'Consultoría del Norte']);
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Alumno 3', $content);
        self::assertStringNotContainsString('Alumno 1', $content);
    }

    public function testListOfAStudentOnlyShowsTheirOwnAgreement(): void
    {
        $year = $this->getOrganization()->getCurrentAcademicYear()->getId();
        $this->login($this->getUser('alfa.alumno3'));

        $this->client->request('GET', "/fct/seguimiento/acuerdo/listar/$year");

        self::assertResponseIsSuccessful();
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Alumno 3', $content);
        self::assertStringNotContainsString('Alumno 1', $content);
    }

    public function testAdministratorCannotListTheAgreementsOfAnotherOrganizationByItsAcademicYear(): void
    {
        $foreignYear = $this->getOrganization(DemoData::ORG_B_CODE)->getCurrentAcademicYear()->getId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', "/fct/seguimiento/acuerdo/listar/$foreignYear");

        self::assertResponseStatusCodeSame(403);
    }
}
