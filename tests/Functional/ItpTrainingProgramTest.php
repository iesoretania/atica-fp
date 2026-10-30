<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Programas formativos de la FFE: listado con búsqueda, alta, edición y borrado. Los gestionan quien administra el
 * centro y el jefe del departamento de las enseñanzas del programa; los tutores de grupo los consultan.
 */
class ItpTrainingProgramTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function year(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->getOrganization($code)->getCurrentAcademicYear()->getId();
    }

    private function programId(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT tp.id FROM itp_training_program tp JOIN itp_program_grade pg ON pg.training_program_id = tp.id JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ? ORDER BY tp.id LIMIT 1',
            [$this->getOrganization($code)->getId()]
        );
    }

    private function programCount(): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM itp_training_program');
    }

    private function gradeId(): int
    {
        return (int) $this->connection()->fetchOne('SELECT grade_id FROM itp_program_grade WHERE training_program_id = ?', [$this->programId()]);
    }

    /**
     * Crea un programa nuevo con el formulario real y devuelve su identificador
     */
    private function createProgram(string $name = 'Zz programa'): int
    {
        $crawler = $this->client->request('GET', '/formacion/plan/nuevo/' . $this->year());
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar cambios')->form(['training_program[name]' => $name]);
        $form['training_program[grades]']->select([(string) $this->gradeId()]);
        $form['training_program[performanceScale]']->select((string) $this->connection()->fetchOne('SELECT id FROM edu_performance_scale WHERE organization_id = ? ORDER BY id LIMIT 1', [$this->getOrganization()->getId()]));
        $this->client->submit($form);
        self::assertResponseRedirects();

        return (int) $this->connection()->fetchOne('SELECT id FROM itp_training_program WHERE name = ?', [$name]);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'jefe de departamento' => ['alfa.jefe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenTheScreens(string $username): void
    {
        $program = $this->programId();
        $this->login($this->getUser($username));

        foreach (['/formacion/plan/listar/' . $this->year(), '/formacion/plan/nuevo/' . $this->year(), "/formacion/plan/$program/" . $this->year()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($username . ' ' . $url);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['profesor', 'tutorlaboral', 'alumno7', 'coordinador', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeThem(string $username): void
    {
        $program = $this->programId();
        $before = $this->programCount();
        $this->login($this->getUser($username));

        foreach (['/formacion/plan/listar/' . $this->year(), '/formacion/plan/nuevo/' . $this->year(), "/formacion/plan/$program/" . $this->year()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $url);
        }
        $this->client->request('POST', '/formacion/plan/eliminar/' . $this->year(), ['items' => [$program], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->programCount());
    }

    public function testAdministratorOfAnotherCentreCannotUseThem(): void
    {
        $program = $this->programId();
        $before = $this->programCount();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (['/formacion/plan/listar/' . $this->year(), "/formacion/plan/$program/" . $this->year()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', '/formacion/plan/eliminar/' . $this->year(), ['items' => [$program], 'confirm' => 'ok']);

        self::assertSame($before, $this->programCount());
    }

    public function testListFiltersBySearch(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/formacion/plan/listar/' . $this->year(), ['q' => 'formación en empresas']);
        self::assertStringContainsString('Programa de formación en empresas', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/formacion/plan/listar/' . $this->year(), ['q' => 'zzzz-no-existe']);
        self::assertStringNotContainsString('Programa de formación en empresas', (string) $this->client->getResponse()->getContent());
    }

    public function testAdministratorCreatesAndEditsAProgram(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->programCount();

        $id = $this->createProgram();

        self::assertSame($before + 1, $this->programCount());
        self::assertSame(1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM itp_program_grade WHERE training_program_id = ?', [$id]));
        $crawler = $this->client->request('GET', "/formacion/plan/$id/" . $this->year());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['training_program[name]' => 'Zz programa editado']));
        self::assertResponseRedirects();
        self::assertSame('Zz programa editado', $this->connection()->fetchOne('SELECT name FROM itp_training_program WHERE id = ?', [$id]));
    }

    public function testProgramWithoutNameOrHoursIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->programCount();

        $crawler = $this->client->request('GET', '/formacion/plan/nuevo/' . $this->year());
        $form = $crawler->selectButton('Guardar cambios')->form(['training_program[name]' => '', 'training_program[totalHours]' => '0']);
        $form['training_program[grades]']->select([(string) $this->gradeId()]);
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->programCount());
    }

    public function testDeletionRequiresConfirmationAndRemovesTheProgram(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $id = $this->createProgram();

        $this->client->request('POST', '/formacion/plan/eliminar/' . $this->year(), ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM itp_training_program WHERE id = ?', [$id]));

        $this->client->request('POST', '/formacion/plan/eliminar/' . $this->year(), ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame(0, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM itp_training_program WHERE id = ?', [$id]));
    }

    public function testDeletingTheProgramOfTheFixturesWithStaysDoesNotFail(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // tiene alumnado, estancias y seguimiento: se borra todo o se informa del error, nunca falla el servidor
        $this->client->request('POST', '/formacion/plan/eliminar/' . $this->year(), ['items' => [$this->programId()], 'confirm' => 'ok']);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testProgramsOfAnotherYearCannotBeDeletedThroughThisOne(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $foreign = $this->programId(DemoData::ORG_B_CODE);
        $before = $this->programCount();

        $this->client->request('POST', '/formacion/plan/eliminar/' . $this->year(), ['items' => [$foreign], 'confirm' => 'ok']);

        self::assertSame($before, $this->programCount());
    }

    public function testITPManagerManagesProgramsAndGroupTutorDoesNot(): void
    {
        $program = $this->programId();

        $this->login($this->getUser('alfa.gestorffe'));
        foreach (['/formacion/plan/listar/' . $this->year(), '/formacion/plan/nuevo/' . $this->year(), "/formacion/plan/$program/" . $this->year()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }

        $this->login($this->getUser('alfa.tutor'));
        foreach (['/formacion/plan/listar/' . $this->year(), "/formacion/plan/$program/" . $this->year()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }
}
