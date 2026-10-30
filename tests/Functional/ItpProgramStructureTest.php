<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Estructura de un programa formativo de la FFE: grupos, alumnado y empresas de cada nivel. Los gestiona quien puede
 * gestionar el programa (administración del centro y jefe de departamento) y nunca se tocan elementos de otro
 * programa o centro aunque se envíen sus identificadores.
 */
class ItpProgramStructureTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function gradeId(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT pg.id FROM itp_program_grade pg JOIN edu_grade g ON g.id = pg.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ? ORDER BY pg.id LIMIT 1',
            [$this->getOrganization($code)->getId()]
        );
    }

    private function groupId(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM itp_program_group WHERE program_grade_id = ? ORDER BY id LIMIT 1', [$this->gradeId($code)]);
    }

    private function studentProgramId(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM itp_student_program WHERE program_group_id = ? ORDER BY id LIMIT 1', [$this->groupId($code)]);
    }

    private function companyProgramId(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM itp_company_program WHERE program_grade_id = ? ORDER BY id LIMIT 1', [$this->gradeId($code)]);
    }

    private function rows(string $table, string $where = '1', array $params = []): int
    {
        return (int) $this->connection()->fetchOne("SELECT COUNT(*) FROM $table WHERE $where", $params);
    }

    /**
     * @return list<string>
     */
    private function readPaths(): array
    {
        return [
            '/formacion/plan/grupo/listar/' . $this->gradeId() . '/1',
            '/formacion/plan/grupo/' . $this->groupId(),
            '/formacion/plan/curso/grupo/estudiante/listar/' . $this->groupId() . '/1',
            '/formacion/plan/curso/grupo/estudiante/' . $this->studentProgramId(),
            '/formacion/plan/empresa/listar/' . $this->gradeId() . '/1',
            '/formacion/plan/empresa/nueva/' . $this->gradeId(),
            '/formacion/plan/empresa/' . $this->companyProgramId(),
        ];
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
        $paths = $this->readPaths();
        $this->login($this->getUser($username));

        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            self::assertResponseIsSuccessful($username . ' ' . $path);
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
        $paths = $this->readPaths();
        $students = $this->rows('itp_student_program');
        $companies = $this->rows('itp_company_program');
        $group = $this->groupId();
        $grade = $this->gradeId();
        $this->login($this->getUser($username));

        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $path);
        }
        $this->client->request('POST', "/formacion/plan/curso/grupo/estudiante/eliminar/$group", ['items' => [$this->studentProgramId()], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', "/formacion/plan/empresa/eliminar/$grade", ['items' => [$this->companyProgramId()], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($students, $this->rows('itp_student_program'));
        self::assertSame($companies, $this->rows('itp_company_program'));
    }

    public function testAdministratorOfAnotherCentreCannotUseThem(): void
    {
        $paths = $this->readPaths();
        $students = $this->rows('itp_student_program');
        $companies = $this->rows('itp_company_program');
        $group = $this->groupId();
        $grade = $this->gradeId();
        $studentProgram = $this->studentProgramId();
        $companyProgram = $this->companyProgramId();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($paths as $path) {
            $this->client->request('GET', $path);
            self::assertResponseStatusCodeSame(403, $path);
        }
        $this->client->request('POST', "/formacion/plan/curso/grupo/estudiante/eliminar/$group", ['items' => [$studentProgram], 'confirm' => 'ok']);
        $this->client->request('POST', "/formacion/plan/empresa/eliminar/$grade", ['items' => [$companyProgram], 'confirm' => 'ok']);

        self::assertSame($students, $this->rows('itp_student_program'));
        self::assertSame($companies, $this->rows('itp_company_program'));
    }

    public function testListsFilterBySearch(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/formacion/plan/empresa/listar/' . $this->gradeId() . '/1', ['q' => 'Tecnologías']);
        self::assertResponseIsSuccessful();
        $found = (string) $this->client->getResponse()->getContent();
        $this->client->request('GET', '/formacion/plan/empresa/listar/' . $this->gradeId() . '/1', ['q' => 'zzzz-no-existe']);
        self::assertLessThan(strlen($found), strlen((string) $this->client->getResponse()->getContent()));

        $this->client->request('GET', '/formacion/plan/curso/grupo/estudiante/listar/' . $this->groupId() . '/1', ['q' => 'zzzz-no-existe']);
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/formacion/plan/grupo/listar/' . $this->gradeId() . '/1', ['q' => 'zzzz-no-existe']);
        self::assertResponseIsSuccessful();
    }

    public function testGroupHoursAndStudentsAreSaved(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $group = $this->groupId();

        $crawler = $this->client->request('GET', "/formacion/plan/grupo/$group");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['program_group[targetHours]' => '120']));

        self::assertResponseRedirects();
        self::assertSame(12000, (int) $this->connection()->fetchOne('SELECT target_hours FROM itp_program_group WHERE id = ?', [$group]));
    }

    public function testStudentProgramIsEdited(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $studentProgram = $this->studentProgramId();

        $crawler = $this->client->request('GET', "/formacion/plan/curso/grupo/estudiante/$studentProgram");
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['student_program[adaptationNeeded]']->select('1');
        $form['student_program[adaptationDescription]'] = 'Adaptación de prueba';
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame('Adaptación de prueba', $this->connection()->fetchOne('SELECT adaptation_description FROM itp_student_program WHERE id = ?', [$studentProgram]));
    }

    public function testStudentProgramsAreDeletedOnlyFromTheirGroup(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $group = $this->groupId();
        $foreign = $this->studentProgramId(DemoData::ORG_B_CODE);

        // sin confirmar solo se pide confirmación
        $this->client->request('POST', "/formacion/plan/curso/grupo/estudiante/eliminar/$group", ['items' => [$this->studentProgramId()]]);
        self::assertResponseIsSuccessful();

        $before = $this->rows('itp_student_program');
        $this->client->request('POST', "/formacion/plan/curso/grupo/estudiante/eliminar/$group", ['items' => [$foreign], 'confirm' => 'ok']);
        self::assertSame($before, $this->rows('itp_student_program'), 'No se borra alumnado de otro grupo ni de otro centro');
    }

    public function testCompanyProgramIsCreatedEditedAndDeleted(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $grade = $this->gradeId();
        $company = (int) $this->connection()->fetchOne('SELECT id FROM company WHERE id NOT IN (SELECT company_id FROM itp_company_program WHERE program_grade_id = ?) ORDER BY id LIMIT 1', [$grade]);
        $before = $this->rows('itp_company_program', 'program_grade_id = ?', [$grade]);

        $crawler = $this->client->request('GET', "/formacion/plan/empresa/nueva/$grade");
        $form = $crawler->selectButton('Guardar cambios')->form(['company_program[agreementNumber]' => 'ZZ-1']);
        $form['company_program[company]']->select((string) $company);
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(200, 'Sin actividades seleccionadas no se guarda');
        self::assertSame($before, $this->rows('itp_company_program', 'program_grade_id = ?', [$grade]));

        $form['company_program[programActivities]'][0]->tick();
        $this->client->submit($form);
        self::assertResponseRedirects();
        self::assertSame($before + 1, $this->rows('itp_company_program', 'program_grade_id = ?', [$grade]));

        $id = (int) $this->connection()->fetchOne('SELECT id FROM itp_company_program WHERE program_grade_id = ? AND company_id = ?', [$grade, $company]);
        $crawler = $this->client->request('GET', "/formacion/plan/empresa/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['company_program[agreementNumber]' => 'ZZ-2']));
        self::assertResponseRedirects();
        self::assertSame('ZZ-2', $this->connection()->fetchOne('SELECT agreement_number FROM itp_company_program WHERE id = ?', [$id]));

        $this->client->request('POST', "/formacion/plan/empresa/eliminar/$grade", ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, $this->rows('itp_company_program', 'id = ?', [$id]));

        $this->client->request('POST', "/formacion/plan/empresa/eliminar/$grade", ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame(0, $this->rows('itp_company_program', 'id = ?', [$id]));
    }

    public function testCompanyProgramsOfAnotherLevelAreNotDeletedThroughThisOne(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $foreign = $this->companyProgramId(DemoData::ORG_B_CODE);
        $before = $this->rows('itp_company_program');

        $this->client->request('POST', '/formacion/plan/empresa/eliminar/' . $this->gradeId(), ['items' => [$foreign], 'confirm' => 'ok']);

        self::assertSame($before, $this->rows('itp_company_program'));
    }
}
