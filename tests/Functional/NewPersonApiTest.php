<?php

namespace App\Tests\Functional;

use App\Entity\Person;

/**
 * Alta de personas desde los formularios de acuerdos y empresas (tutorías laborales, gerentes): la persona nueva
 * entra con su identificador como contraseña, así que debe cambiarla en el primer acceso.
 */
class NewPersonApiTest extends AppWebTestCase
{
    private function create(string $username, string $identifier): ?Person
    {
        $crawler = $this->client->request('GET', '/api/persona/crear');
        $form = $crawler->filter('form')->form();
        $form['new_person[uniqueIdentifier]'] = $identifier;
        $form['new_person[loginUsername]'] = $username;
        $form['new_person[firstName]'] = 'Rosa';
        $form['new_person[lastName]'] = 'Tutora Laboral';
        $this->client->submit($form);

        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em->getRepository(Person::class)->findOneBy(['loginUsername' => $username]);
    }

    public function testNewPersonMustChangeTheInitialPassword(): void
    {
        $this->login($this->getUser('alfa.jefe'));

        $person = $this->create('rtutora', '12345678Z');

        self::assertResponseIsSuccessful();
        self::assertInstanceOf(Person::class, $person);
        self::assertTrue($person->isForcePasswordChange());
    }

    public function testNewPersonWithoutIdentifierIsCreatedToo(): void
    {
        $this->login($this->getUser('alfa.jefe'));

        $person = $this->create('sinidentificador', '');

        self::assertResponseIsSuccessful();
        self::assertInstanceOf(Person::class, $person);
        self::assertTrue($person->isForcePasswordChange());
    }

    public function testTeachersWithoutCompanyPermissionCannotCreatePeople(): void
    {
        $this->login($this->getUser('alfa.profesor'));

        $this->client->request('GET', '/api/persona/crear');

        self::assertResponseStatusCodeSame(403);
    }
}
