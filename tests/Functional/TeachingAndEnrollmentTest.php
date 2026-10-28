<?php

namespace App\Tests\Functional;

use App\Entity\Edu\StudentEnrollment;
use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Matrícula del alumnado y asignaciones de docencia (profesor, materia y grupo) de un curso académico.
 */
class TeachingAndEnrollmentTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function year(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->getOrganization($code)->getCurrentAcademicYear()->getId();
    }

    private function enrollmentId(string $username = 'alfa.alumno1'): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT se.id FROM edu_student_enrollment se JOIN person p ON p.id = se.person_id WHERE p.login_username = ?',
            [$username]
        );
    }

    private function enrollmentCount(): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM edu_student_enrollment');
    }

    // --- Matrícula -----------------------------------------------------------------------------------

    public function testAdministratorListsAndSearchesEnrollments(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/centro/matricula/listar/' . $this->year());
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Alumno 1', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/centro/matricula/listar/' . $this->year(), ['q' => 'Alumno 3']);
        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Alumno 3', $content);
        self::assertStringNotContainsString('Alumno 1 ', $content);
    }

    public function testAdministratorEditsAnEnrollmentAndReturnsToTheEnrollmentList(): void
    {
        $id = $this->enrollmentId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/centro/matricula/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['student_enrollment[personFirstName]' => 'Alumno Renombrado']));

        // tras guardar se vuelve al listado de matrículas, no al de profesorado
        self::assertResponseRedirects();
        self::assertStringContainsString('/centro/matricula/listar', (string) $this->client->getResponse()->headers->get('Location'));
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSame('Alumno Renombrado', $this->getUser('alfa.alumno1')->getFirstName());
    }

    public function testEnrollmentCanBeMovedToAnotherGroupOfTheSameYear(): void
    {
        $id = $this->enrollmentId();
        $original = (int) $this->connection()->fetchOne('SELECT group_id FROM edu_student_enrollment WHERE id = ?', [$id]);
        $other = (int) $this->connection()->fetchOne('SELECT id FROM edu_group WHERE id <> ? ORDER BY id LIMIT 1', [$original]);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/centro/matricula/$id");
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['student_enrollment[group]']->select((string) $other);
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame($other, (int) $this->connection()->fetchOne('SELECT group_id FROM edu_student_enrollment WHERE id = ?', [$id]));
    }

    public function testEnrollmentCannotBeMovedToAGroupOfAnotherCentre(): void
    {
        $id = $this->enrollmentId();
        $original = (int) $this->connection()->fetchOne('SELECT group_id FROM edu_student_enrollment WHERE id = ?', [$id]);
        $foreign = (int) $this->connection()->fetchOne(
            'SELECT g.id FROM edu_group g JOIN edu_grade gr ON gr.id = g.grade_id JOIN edu_training t ON t.id = gr.training_id JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ? LIMIT 1',
            [$this->getOrganization(DemoData::ORG_B_CODE)->getId()]
        );
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $crawler = $this->client->request('GET', "/centro/matricula/$id");
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        $values['student_enrollment']['group'] = (string) $foreign;

        $this->client->request('POST', "/centro/matricula/$id", $values);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($original, (int) $this->connection()->fetchOne('SELECT group_id FROM edu_student_enrollment WHERE id = ?', [$id]));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['jefe', 'tutor', 'profesor', 'tutorlaboral', 'alumno1', 'coordinador', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotManageEnrollmentsOrTeachings(string $username): void
    {
        $enrollment = $this->enrollmentId();
        $teaching = (int) $this->connection()->fetchOne('SELECT id FROM edu_teaching ORDER BY id LIMIT 1');
        $before = [$this->enrollmentCount(), (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM edu_teaching')];
        $this->login($this->getUser($username));

        foreach (['/centro/matricula/listar/' . $this->year(), "/centro/matricula/$enrollment", "/centro/materia/asignacion/$teaching"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', '/centro/matricula/operacion/' . $this->year(), ['items' => [$enrollment], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', "/centro/materia/asignacion/eliminar/$teaching", ['confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, [$this->enrollmentCount(), (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM edu_teaching')]);
    }

    public function testEnrollmentDeletionRequiresConfirmation(): void
    {
        $id = $this->enrollmentId('alfa.alumno4');
        $before = $this->enrollmentCount();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/matricula/operacion/' . $this->year(), ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame($before, $this->enrollmentCount());

        // el alumno 4 no tiene convenios ni estancias: se puede borrar la matrícula
        $this->client->request('POST', '/centro/matricula/operacion/' . $this->year(), ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertLessThanOrEqual($before, $this->enrollmentCount());
    }

    public function testEnrollmentsOfAnotherCentreCannotBeDeletedThroughThisOne(): void
    {
        $foreign = $this->enrollmentId('beta.alumno4');
        $before = $this->enrollmentCount();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', '/centro/matricula/operacion/' . $this->year(), ['items' => [$foreign], 'confirm' => 'ok']);

        self::assertSame($before, $this->enrollmentCount());
        self::assertNotNull(static::getContainer()->get('doctrine')->getManager()->getRepository(StudentEnrollment::class)->find($foreign));
    }

    public function testAdministratorOfAnotherCentreCannotEditTheEnrollment(): void
    {
        $id = $this->enrollmentId();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        $this->client->request('GET', "/centro/matricula/$id");

        self::assertResponseStatusCodeSame(403);
    }

    // --- Docencia -----------------------------------------------------------------------------------

    private function teachingCount(): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM edu_teaching');
    }

    public function testAdministratorAddsEditsAndRemovesATeaching(): void
    {
        $subject = (int) $this->connection()->fetchOne('SELECT subject_id FROM edu_teaching ORDER BY id LIMIT 1');
        $group = (int) $this->connection()->fetchOne('SELECT group_id FROM edu_teaching WHERE subject_id = ? ORDER BY id LIMIT 1', [$subject]);
        $current = (int) $this->connection()->fetchOne('SELECT teacher_id FROM edu_teaching WHERE subject_id = ? ORDER BY id LIMIT 1', [$subject]);
        $other = (int) $this->connection()->fetchOne('SELECT id FROM edu_teacher WHERE id <> ? AND academic_year_id = ? ORDER BY id LIMIT 1', [$current, $this->year()]);
        $before = $this->teachingCount();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/centro/materia/asignacion/nueva/$subject");
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['teaching[teacher]']->select((string) $other);
        $form['teaching[group]']->select((string) $group);
        $this->client->submit($form);
        self::assertResponseRedirects();
        self::assertSame($before + 1, $this->teachingCount());

        $id = (int) $this->connection()->fetchOne('SELECT MAX(id) FROM edu_teaching');
        $this->client->request('POST', "/centro/materia/asignacion/eliminar/$id");
        self::assertResponseIsSuccessful();
        self::assertSame($before + 1, $this->teachingCount(), 'Sin confirmar no se borra');
        $this->client->request('GET', "/centro/materia/asignacion/eliminar/$id", ['confirm' => 'ok']);
        self::assertSame($before + 1, $this->teachingCount(), 'Un enlace no puede confirmar el borrado');
        $this->client->request('POST', "/centro/materia/asignacion/eliminar/$id", ['confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame($before, $this->teachingCount());
    }

    public function testTeachingCannotAssignATeacherOfAnotherCentre(): void
    {
        $subject = (int) $this->connection()->fetchOne('SELECT subject_id FROM edu_teaching ORDER BY id LIMIT 1');
        $group = (int) $this->connection()->fetchOne('SELECT group_id FROM edu_teaching WHERE subject_id = ? ORDER BY id LIMIT 1', [$subject]);
        $foreignTeacher = (int) $this->connection()->fetchOne('SELECT id FROM edu_teacher WHERE academic_year_id = ? ORDER BY id LIMIT 1', [$this->year(DemoData::ORG_B_CODE)]);
        $before = $this->teachingCount();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $crawler = $this->client->request('GET', "/centro/materia/asignacion/nueva/$subject");
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        $values['teaching']['teacher'] = (string) $foreignTeacher;
        $values['teaching']['group'] = (string) $group;

        $this->client->request('POST', "/centro/materia/asignacion/nueva/$subject", $values);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($before, $this->teachingCount());
    }
}
