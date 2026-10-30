<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Gestión de las estancias de un grupo de la FFE por el jefe de departamento y la administración: listado, alta
 * por lotes, edición, copia de calendarios y borrado. Estancias de las fixtures (programa del centro Alfa):
 *  - 1: alumno7 en "Sede central" (empresa 1), 2: alumno8 en "Oficina de Úbeda" (empresa 2)
 */
class ItpStayManagerTest extends AppWebTestCase
{
    private const BASE = '/formacion/plan/curso/grupo/gestion';

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

    /**
     * Estancia de la persona indicada
     */
    private function stayOf(string $username): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT w.id FROM itp_student_program_workcenter w JOIN itp_student_program sp ON sp.id = w.student_program_id JOIN edu_student_enrollment se ON se.id = sp.student_enrollment_id JOIN person p ON p.id = se.person_id WHERE p.login_username = ? ORDER BY w.id LIMIT 1',
            [$username]
        );
    }

    private function stays(): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM itp_student_program_workcenter');
    }

    /**
     * @return array<string, mixed> valores del formulario de alta por lotes, listos para enviar
     */
    private function batchValues(string $student, int $workcenter, bool $withActivity = true): array
    {
        $conn = $this->connection();
        $company = (int) $conn->fetchOne('SELECT company_id FROM workcenter WHERE id = ?', [$workcenter]);

        $crawler = $this->client->request('GET', self::BASE . '/nueva/' . $this->groupId());
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar cambios')->form();
        $values = $form->getPhpValues();
        $name = 'student_program_workcenter_batch';
        // los tutores docentes posibles son los profesores del grupo
        $tutor = array_values(array_filter($form["{$name}[educationalTutor]"]->availableOptionValues()))[0];
        // las opciones de alumnado y de actividades son posiciones de una lista, no identificadores
        $person = $conn->fetchAssociative('SELECT first_name, last_name FROM person WHERE login_username = ?', [$student]);
        $studentValue = null;
        foreach ($crawler->filter("input[name=\"{$name}[students][]\"]") as $input) {
            $label = $crawler->filter('label[for="' . $input->getAttribute('id') . '"]')->text();
            if (str_contains($label, $person['last_name']) && str_contains($label, $person['first_name'])) {
                $studentValue = $input->getAttribute('value');
            }
        }
        self::assertNotNull($studentValue, 'El alumno debe aparecer en el formulario');
        $values[$name]['students'] = [$studentValue];
        $values[$name]['company'] = $company;
        $values[$name]['workcenter'] = $workcenter;
        $values[$name]['educationalTutor'] = $tutor;
        $values[$name]['workTutor'] = (int) $conn->fetchOne('SELECT id FROM person WHERE login_username = ?', ['alfa.tutorlaboral']);
        $values[$name]['startDate'] = '2026-09-15';
        $values[$name]['endDate'] = '2026-11-24';
        if ($withActivity) {
            $values[$name]['selectedActivities'] = ['0'];
        }

        return $values;
    }

    private function teacherId(string $username): int
    {
        return (int) $this->connection()->fetchOne('SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', [$username]);
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
        $this->login($this->getUser($username));

        foreach ([
            self::BASE . '/listar/' . $this->gradeId() . '/1',
            self::BASE . '/nueva/' . $this->groupId(),
            self::BASE . '/detalle/' . $this->stayOf('alfa.alumno7'),
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($username . ' ' . $url);
        }
        $this->client->request('GET', self::BASE . '/listar/' . $this->gradeId() . '/1', ['q' => 'zzzz-no-existe']);
        self::assertResponseIsSuccessful();
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
    public function testOthersCannotSeeOrChangeStays(string $username): void
    {
        $stay = $this->stayOf('alfa.alumno7');
        $before = $this->stays();
        $this->login($this->getUser($username));

        foreach ([
            self::BASE . '/listar/' . $this->gradeId() . '/1',
            self::BASE . '/nueva/' . $this->groupId(),
            self::BASE . "/detalle/$stay",
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $url);
        }
        $this->client->request('POST', self::BASE . '/operacion/' . $this->gradeId(), ['items' => [$stay], 'delete' => '', 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->stays());
    }

    public function testAdministratorOfAnotherCentreCannotUseThem(): void
    {
        $stay = $this->stayOf('alfa.alumno7');
        $before = $this->stays();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ([self::BASE . '/listar/' . $this->gradeId() . '/1', self::BASE . '/nueva/' . $this->groupId(), self::BASE . "/detalle/$stay"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', self::BASE . '/operacion/' . $this->gradeId(), ['items' => [$stay], 'delete' => '', 'confirm' => 'ok']);

        self::assertSame($before, $this->stays());
    }

    public function testBatchCreationAddsAStayForEachSelectedStudent(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->stays();

        // alumno8 en otra sede de la empresa 1
        $crawler = $this->client->request('POST', self::BASE . '/nueva/' . $this->groupId(), $this->batchValues('alfa.alumno8', 2));

        self::assertResponseRedirects(null, null, implode(' | ', $crawler->filter('.form-error-message, .alert')->each(static fn($n) => trim(preg_replace('/\s+/', ' ', $n->text())))));
        self::assertSame($before + 1, $this->stays());
        self::assertSame(1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM itp_student_program_workcenter_activity a JOIN itp_student_program_workcenter w ON w.id = a.student_program_workcenter_id WHERE w.workcenter_id = 2'));
    }

    public function testBatchCreationRejectsMissingActivitiesStudentsAndRepeatedStays(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->stays();

        // sin actividades
        $this->client->request('POST', self::BASE . '/nueva/' . $this->groupId(), $this->batchValues('alfa.alumno8', 2, false));
        self::assertResponseStatusCodeSame(200);

        // sin alumnado
        $values = $this->batchValues('alfa.alumno8', 2);
        unset($values['student_program_workcenter_batch']['students']);
        $this->client->request('POST', self::BASE . '/nueva/' . $this->groupId(), $values);
        self::assertResponseStatusCodeSame(200);

        // la misma estancia ya existente (alumno7 en la sede 1)
        $this->client->request('POST', self::BASE . '/nueva/' . $this->groupId(), $this->batchValues('alfa.alumno7', 1));
        self::assertResponseStatusCodeSame(200);

        self::assertSame($before, $this->stays(), 'No se duplica una estancia del mismo alumno en el mismo centro');
    }

    public function testBatchCreationWithoutCompanyDoesNotFail(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $values = $this->batchValues('alfa.alumno8', 2);
        unset($values['student_program_workcenter_batch']['company']);
        $this->client->request('POST', self::BASE . '/nueva/' . $this->groupId(), $values);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    public function testStayDatesAreEdited(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $stay = $this->stayOf('alfa.alumno7');

        $crawler = $this->client->request('GET', self::BASE . "/detalle/$stay");
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['student_program_workcenter[educationalTutor]']->select(array_values(array_filter($form['student_program_workcenter[educationalTutor]']->availableOptionValues()))[0]);
        $form['student_program_workcenter[endDate]'] = '2026-12-15';
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame('2026-12-15', $this->connection()->fetchOne('SELECT end_date FROM itp_student_program_workcenter WHERE id = ?', [$stay]));
    }

    public function testCalendarIsCopiedFromAnotherStay(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $source = $this->stayOf('alfa.alumno7');
        $target = $this->stayOf('alfa.alumno8');
        $grade = $this->gradeId();
        $days = fn(int $stay): int => (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM itp_work_day WHERE student_program_workcenter_id = ?', [$stay]);
        $this->connection()->executeStatement('DELETE FROM itp_work_day_activity WHERE work_day_id IN (SELECT id FROM itp_work_day WHERE student_program_workcenter_id = ?)', [$target]);
        $this->connection()->executeStatement('DELETE FROM itp_work_day WHERE student_program_workcenter_id = ?', [$target]);
        self::assertSame(0, $days($target));

        $crawler = $this->client->request('POST', self::BASE . "/operacion/$grade", ['items' => [$target], 'copy' => '']);
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form')->first()->form();
        $form['calendar_copy[sourceStudentProgramWorkcenter]']->select((string) $source);
        // la plantilla anida dos formularios y el navegador los fusiona: el botón de copiar envía "copy"
        $this->client->request('POST', $form->getUri(), $form->getPhpValues() + ['copy' => '']);

        self::assertResponseRedirects();
        self::assertSame($days($source), $days($target));
    }

    public function testStaysAreDeletedOnlyWithConfirmationAndOnlyFromThisGrade(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $grade = $this->gradeId();
        $foreign = $this->connection()->fetchOne(
            'SELECT w.id FROM itp_student_program_workcenter w JOIN itp_student_program sp ON sp.id = w.student_program_id JOIN itp_program_group g ON g.id = sp.program_group_id WHERE g.program_grade_id = ? ORDER BY w.id LIMIT 1',
            [$this->gradeId(DemoData::ORG_B_CODE)]
        );
        $before = $this->stays();

        $this->client->request('POST', self::BASE . "/operacion/$grade", ['items' => [$foreign], 'delete' => '', 'confirm' => 'ok']);
        self::assertSame($before, $this->stays(), 'No se borran estancias de otro programa');

        $stay = $this->stayOf('alfa.alumno8');
        $this->client->request('POST', self::BASE . "/operacion/$grade", ['items' => [$stay], 'delete' => '']);
        self::assertResponseIsSuccessful();
        self::assertSame($before, $this->stays());

        $this->client->request('POST', self::BASE . "/operacion/$grade", ['items' => [$stay], 'delete' => '', 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame($before - 1, $this->stays());
    }

    public function testOperationWithoutItemsReturnsToTheList(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $grade = $this->gradeId();

        $this->client->request('POST', self::BASE . "/operacion/$grade", []);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }
}
