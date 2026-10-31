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

    private function connection(): \Doctrine\DBAL\Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function teacherId(string $username): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ? ORDER BY t.id DESC LIMIT 1',
            [$username]
        );
    }

    private function meetingId(): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM wlt_meeting ORDER BY id LIMIT 1');
    }

    public function testGroupTutorRegistersAMeetingAndIsItsCreator(): void
    {
        $year = $this->getOrganization()->getCurrentAcademicYear()->getId();
        $tutor = $this->teacherId('alfa.tutor');
        $before = (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_meeting');
        $this->login($this->getUser('alfa.tutor'));

        $crawler = $this->client->request('GET', '/dual/reunion/nueva/' . $year);
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        $values['meeting']['dateTime'] = ['date' => '2026-11-12', 'time' => '10:30'];
        $values['meeting']['createdBy'] = (string) $tutor;
        $values['meeting']['project'] = (string) $this->connection()->fetchOne('SELECT id FROM wlt_project LIMIT 1');
        $values['meeting']['teachers'] = [(string) $tutor];
        $values['meeting']['detail'] = 'Se revisa el calendario de alternancia';
        $this->client->request('POST', '/dual/reunion/nueva/' . $year, $values);

        self::assertResponseRedirects('/dual/reunion/listar');
        self::assertSame($before + 1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_meeting'));
        $row = $this->connection()->fetchAssociative('SELECT created_by_id, detail FROM wlt_meeting ORDER BY id DESC LIMIT 1');
        self::assertSame($tutor, (int) $row['created_by_id']);
        self::assertSame('Se revisa el calendario de alternancia', $row['detail']);
    }

    /**
     * La jefatura de departamento ve las reuniones de su ámbito pero solo las puede modificar quien las creó
     */
    public function testAnotherTeacherOpensTheMeetingReadOnlyAndCannotChangeIt(): void
    {
        $id = $this->meetingId();
        $detail = (string) $this->connection()->fetchOne('SELECT detail FROM wlt_meeting WHERE id = ?', [$id]);
        $this->login($this->getUser('alfa.jefe'));

        $crawler = $this->client->request('GET', '/dual/reunion/' . $id);

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->selectButton('Guardar cambios'), 'La jefatura ve el botón de guardar en una reunión ajena');

        $this->client->request('POST', '/dual/reunion/' . $id, ['meeting' => ['detail' => 'Modificada por otra persona']]);

        self::assertSame($detail, (string) $this->connection()->fetchOne('SELECT detail FROM wlt_meeting WHERE id = ?', [$id]));
    }

    public function testCreatorSeesTheSaveButtonOfTheirMeeting(): void
    {
        $this->login($this->getUser('alfa.tutor'));

        $crawler = $this->client->request('GET', '/dual/reunion/' . $this->meetingId());

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->selectButton('Guardar cambios'));
    }

    public function testDeletingAMeetingAsksForConfirmationAndOnlyTheCreatorOrAnAdministratorCanDoIt(): void
    {
        $id = $this->meetingId();
        $count = (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_meeting');

        // otra persona con acceso a la reunión no puede eliminarla, ni siquiera pidiendo la confirmación
        $this->login($this->getUser('alfa.jefe'));
        $this->client->request('POST', '/dual/reunion/eliminar', ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        self::assertSame($count, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_meeting'));

        // quien la creó recibe primero la página de confirmación, que no borra nada
        static::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->login($this->getUser('alfa.tutor'));
        $this->client->request('POST', '/dual/reunion/eliminar', ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame($count, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_meeting'));

        // y al confirmar se elimina
        $this->client->request('POST', '/dual/reunion/eliminar', ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects('/dual/reunion/listar');
        self::assertSame($count - 1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wlt_meeting'));
    }

    public function testStudentCannotOpenTheMeetingForms(): void
    {
        $this->login($this->getUser('alfa.alumno5'));

        $this->client->request('GET', '/dual/reunion/nueva/' . $this->getOrganization()->getCurrentAcademicYear()->getId());

        self::assertResponseStatusCodeSame(403);
    }
}
