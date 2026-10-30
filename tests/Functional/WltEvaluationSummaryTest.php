<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Agreement;
use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Resumen de resultados del alumnado de FP dual: listado con búsqueda e informe de cada estudiante. Lo ven la
 * administración, la coordinación, los jefes de departamento y el profesorado dual y los tutores de grupo, estos
 * últimos con las materias que imparten. El alumnado de las fixtures está en el grupo "2º DAM A".
 */
class WltEvaluationSummaryTest extends AppWebTestCase
{
    private const BASE = '/dual/resultado/estudiante';

    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    private function enrollmentId(string $username): int
    {
        return (int) $this->connection()->fetchOne('SELECT se.id FROM edu_student_enrollment se JOIN person p ON p.id = se.person_id WHERE p.login_username = ? ORDER BY se.id LIMIT 1', [$username]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function viewerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'coordinador' => ['alfa.coordinador'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'tutor de grupo' => ['alfa.tutor'];
    }

    /**
     * @dataProvider viewerProvider
     */
    public function testViewersOpenTheListAndTheReport(string $username): void
    {
        $this->login($this->getUser($username));

        foreach (['', '?q=zzzz-no-existe', '?q=Alumno'] as $query) {
            $this->client->request('GET', self::BASE . '/listar/' . $this->year() . "/1$query");
            self::assertResponseIsSuccessful("$username lista$query");
        }
        $this->client->request('GET', self::BASE . '/' . $this->enrollmentId('alfa.alumno5'));
        self::assertResponseIsSuccessful("$username informe");
    }

    public function testReportShowsTheRemarksOfTheWorkTutor(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $agreement = $em->getRepository(Agreement::class)->findOneBy(['studentEnrollment' => $this->enrollmentId('alfa.alumno5')]);
        $agreement->setWorkTutorRemarks('Valoración de la empresa muy positiva');
        $em->flush();
        $em->clear();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', self::BASE . '/' . $this->enrollmentId('alfa.alumno5'));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('Valoración de la empresa muy positiva', (string) $this->client->getResponse()->getContent());
    }

    public function testATeacherOnlySeesTheReportOfTheStudentsTheyTeach(): void
    {
        // "profesor" da clase en 1º DAM A, que pasa a formar parte del proyecto de FP dual; el alumnado con acuerdos
        // (alumno5 y alumno6) está en 2º DAM A, donde no imparte
        $conn = $this->connection();
        $project = (int) $conn->fetchOne('SELECT id FROM wlt_project WHERE organization_id = ? ORDER BY id LIMIT 1', [$this->getOrganization()->getId()]);
        $conn->executeStatement('INSERT INTO wlt_project_group (project_id, group_id) VALUES (?, 1)', [$project]);
        $this->login($this->getUser('alfa.profesor'));

        $this->client->request('GET', self::BASE . '/' . $this->enrollmentId('alfa.alumno4'));
        self::assertResponseIsSuccessful('Su propio alumnado');

        foreach (['alfa.alumno5', 'alfa.alumno6'] as $student) {
            $this->client->request('GET', self::BASE . '/' . $this->enrollmentId($student));
            self::assertResponseStatusCodeSame(403, $student);
        }
    }

    public function testTeachersOfTheGroupAndTheGroupTutorStillSeeTheirStudents(): void
    {
        // "tutor" da clase y es tutor del grupo 2º DAM A
        $this->login($this->getUser('alfa.tutor'));

        foreach (['alfa.alumno5', 'alfa.alumno6'] as $student) {
            $this->client->request('GET', self::BASE . '/' . $this->enrollmentId($student));
            self::assertResponseIsSuccessful($student);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['alumno5', 'alumno6', 'tutorlaboral', 'economico', 'gestorffe'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeTheSummary(string $username): void
    {
        $this->login($this->getUser($username));

        $this->client->request('GET', self::BASE . '/listar/' . $this->year() . '/1');
        self::assertResponseStatusCodeSame(403, "$username lista");
        $this->client->request('GET', self::BASE . '/' . $this->enrollmentId('alfa.alumno5'));
        self::assertResponseStatusCodeSame(403, "$username informe");
    }

    public function testAdministratorOfAnotherCentreCannotSeeIt(): void
    {
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        $this->client->request('GET', self::BASE . '/listar/' . $this->year() . '/1');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', self::BASE . '/' . $this->enrollmentId('alfa.alumno5'));
        self::assertResponseStatusCodeSame(403);
    }
}
