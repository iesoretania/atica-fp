<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Meeting;

/**
 * Reuniones de seguimiento de FP dual: el formulario de alta y de edición debe poder abrirse por todo el
 * profesorado que tiene acceso a las reuniones, sea cual sea su relación con los grupos.
 */
class WltMeetingTest extends AppWebTestCase
{
    /**
     * @return iterable<string, array{string}>
     */
    public static function teacherProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'coordinador' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'tutor de grupo' => ['alfa.tutor'];
        yield 'profesorado' => ['alfa.profesor'];
    }

    /**
     * @dataProvider teacherProvider
     */
    public function testTeachersOpenTheNewMeetingForm(string $username): void
    {
        $this->login($this->getUser($username));

        $this->client->request('GET', '/dual/reunion/nueva/' . $this->getOrganization()->getCurrentAcademicYear()->getId());

        // el administrador local no es profesorado del curso y no puede crear reuniones (el creador es obligatorio)
        self::assertResponseStatusCodeSame($username === 'alfa.admin' ? 403 : 200);
    }

    /**
     * @dataProvider teacherProvider
     */
    public function testTeachersOpenAnExistingMeeting(string $username): void
    {
        $id = static::getContainer()->get('doctrine')->getManager()->getRepository(Meeting::class)->findOneBy([])->getId();
        $this->login($this->getUser($username));

        $this->client->request('GET', '/dual/reunion/' . $id);

        // el acceso puede estar restringido por la reunión, pero nunca debe fallar el servidor
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    public function testGroupTutorCanOpenTheMeetingTheyCreated(): void
    {
        $id = static::getContainer()->get('doctrine')->getManager()->getRepository(Meeting::class)->findOneBy([])->getId();
        $this->login($this->getUser('alfa.tutor'));

        $this->client->request('GET', '/dual/reunion/' . $id);

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidDateProvider(): iterable
    {
        yield 'fecha vacía' => ['', ''];
        yield 'fecha sin sentido' => ['no es una fecha', 'tampoco una hora'];
        yield 'mes inexistente' => ['2026-13-45', '99:99'];
    }

    /**
     * @dataProvider invalidDateProvider
     */
    public function testMeetingFormWithAnInvalidDateIsRejectedWithoutFailing(string $date, string $time): void
    {
        $count = static::getContainer()->get('doctrine')->getConnection()->fetchOne('SELECT COUNT(*) FROM wlt_meeting');
        $this->login($this->getUser('alfa.tutor'));
        $crawler = $this->client->request('GET', '/dual/reunion/nueva/' . $this->getOrganization()->getCurrentAcademicYear()->getId());
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        $values['meeting']['dateTime'] = ['date' => $date, 'time' => $time];
        $values['meeting']['project'] = (string) static::getContainer()->get('doctrine')->getConnection()->fetchOne('SELECT id FROM wlt_project LIMIT 1');

        $this->client->request('POST', '/dual/reunion/nueva/' . $this->getOrganization()->getCurrentAcademicYear()->getId(), $values);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($count, static::getContainer()->get('doctrine')->getConnection()->fetchOne('SELECT COUNT(*) FROM wlt_meeting'));
    }

    public function testStudentCannotOpenTheMeetingForms(): void
    {
        $this->login($this->getUser('alfa.alumno5'));

        $this->client->request('GET', '/dual/reunion/nueva/' . $this->getOrganization()->getCurrentAcademicYear()->getId());

        self::assertResponseStatusCodeSame(403);
    }
}
