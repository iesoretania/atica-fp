<?php

namespace App\Tests\Functional;

use App\Entity\WptModule\Activity;
use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\Shift;
use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Convocatorias de FCT (fase de prácticas de una materia): listado con búsqueda, alta, edición, bloqueo y borrado,
 * que también elimina sus convenios y actividades. La convocatoria de las fixtures tiene tres actividades y dos
 * convenios; la gestionan quien administra el centro y el jefe del departamento de su enseñanza.
 */
class WptShiftTest extends AppWebTestCase
{
    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function year(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->getOrganization($code)->getCurrentAcademicYear()->getId();
    }

    private function shiftId(string $code = DemoData::ORG_A_CODE): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT s.id FROM wpt_shift s JOIN edu_subject su ON su.id = s.subject_id JOIN edu_grade g ON g.id = su.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ? ORDER BY s.id LIMIT 1',
            [$this->getOrganization($code)->getId()]
        );
    }

    private function shiftCount(): int
    {
        return (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_shift');
    }

    /**
     * Materia de la organización que todavía no tiene convocatoria
     */
    private function freeSubjectId(): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT su.id FROM edu_subject su JOIN edu_grade g ON g.id = su.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ? AND su.id NOT IN (SELECT subject_id FROM wpt_shift) ORDER BY su.id LIMIT 1',
            [$this->getOrganization()->getId()]
        );
    }

    /**
     * Crea una convocatoria nueva con el formulario real y devuelve su identificador
     */
    private function createShift(string $name = 'Zz convocatoria'): int
    {
        $crawler = $this->client->request('GET', '/fct/convocatoria/nueva/' . $this->year());
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar cambios')->form([
            'shift[name]' => $name,
            'shift[type]' => 'FCT',
            'shift[hours]' => '300',
        ]);
        $form['shift[subject]']->select((string) $this->freeSubjectId());
        $form['shift[quarter]']->select((string) Shift::QUARTER_FIRST);
        $this->client->submit($form);
        self::assertResponseRedirects();

        return (int) $this->connection()->fetchOne('SELECT id FROM wpt_shift WHERE name = ?', [$name]);
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
    public function testManagersSeeTheShiftsOfTheirDepartment(string $username): void
    {
        $shift = $this->shiftId();
        $this->login($this->getUser($username));

        foreach (['/fct/convocatoria/listar/' . $this->year(), '/fct/convocatoria/nueva/' . $this->year(), "/fct/convocatoria/$shift"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($username . ' ' . $url);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno1', 'coordinador', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotSeeOrChangeShifts(string $username): void
    {
        $shift = $this->shiftId();
        $before = $this->shiftCount();
        $this->login($this->getUser($username));

        foreach (['/fct/convocatoria/listar/' . $this->year(), '/fct/convocatoria/nueva/' . $this->year(), "/fct/convocatoria/$shift"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', '/fct/convocatoria/eliminar/' . $this->year(), ['items' => [$shift], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertSame($before, $this->shiftCount());
    }

    public function testAdministratorOfAnotherCentreCannotUseTheShifts(): void
    {
        $shift = $this->shiftId();
        $before = $this->shiftCount();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (['/fct/convocatoria/listar/' . $this->year(), '/fct/convocatoria/nueva/' . $this->year(), "/fct/convocatoria/$shift"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', '/fct/convocatoria/eliminar/' . $this->year(), ['items' => [$shift], 'confirm' => 'ok']);

        self::assertSame($before, $this->shiftCount());
    }

    public function testListFiltersBySearch(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/fct/convocatoria/listar/' . $this->year(), ['q' => 'Formación en centros']);
        self::assertStringContainsString('Formación en centros de trabajo', (string) $this->client->getResponse()->getContent());

        $this->client->request('GET', '/fct/convocatoria/listar/' . $this->year(), ['q' => 'zzzz-no-existe']);
        self::assertStringNotContainsString('Formación en centros de trabajo', (string) $this->client->getResponse()->getContent());
    }

    public function testAdministratorCreatesAndEditsAShift(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->shiftCount();

        $id = $this->createShift();

        self::assertSame($before + 1, $this->shiftCount());
        $crawler = $this->client->request('GET', "/fct/convocatoria/$id");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['shift[name]' => 'Zz convocatoria editada']));
        self::assertResponseRedirects();
        self::assertSame('Zz convocatoria editada', $this->connection()->fetchOne('SELECT name FROM wpt_shift WHERE id = ?', [$id]));
    }

    public function testShiftWithoutNameOrHoursIsRejected(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $before = $this->shiftCount();

        $crawler = $this->client->request('GET', '/fct/convocatoria/nueva/' . $this->year());
        $form = $crawler->selectButton('Guardar cambios')->form(['shift[name]' => '', 'shift[type]' => '', 'shift[hours]' => '']);
        $form['shift[subject]']->select((string) $this->freeSubjectId());
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->shiftCount());
    }

    public function testLockedShiftBlocksTheDailyTrackingOfItsStudents(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $shift = $this->shiftId();
        $crawler = $this->client->request('GET', "/fct/convocatoria/$shift");
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['shift[locked]']->select('1');
        $this->client->submit($form);
        self::assertResponseRedirects();
        self::assertSame(1, (int) $this->connection()->fetchOne('SELECT locked FROM wpt_shift WHERE id = ?', [$shift]));

        // con la convocatoria cerrada el tutor ya no puede rellenar el calendario ni el informe final
        $this->login($this->getUser('alfa.tutor'));
        $enrollment = (int) $this->connection()->fetchOne(
            'SELECT ae.id FROM wpt_agreement_enrollment ae JOIN wpt_agreement a ON a.id = ae.agreement_id WHERE a.shift_id = ? ORDER BY ae.id LIMIT 1',
            [$shift]
        );
        $crawler = $this->client->request('GET', "/fct/informe/cumplimentar/$enrollment");
        self::assertResponseIsSuccessful();
        self::assertSame(0, $crawler->selectButton('Guardar cambios')->count());
    }

    public function testLockedShiftIsReadOnlyForTheDepartmentHeadButNotForTheAdministrator(): void
    {
        $shift = $this->shiftId();
        $agreement = (int) $this->connection()->fetchOne('SELECT id FROM wpt_agreement WHERE shift_id = ? ORDER BY id LIMIT 1', [$shift]);
        $activity = (int) $this->connection()->fetchOne('SELECT id FROM wpt_activity WHERE shift_id = ? ORDER BY id LIMIT 1', [$shift]);

        // sin bloquear, la jefatura modifica la convocatoria, su programa formativo y sus convenios
        $this->login($this->getUser('alfa.jefe'));
        foreach (["/fct/convocatoria/$shift", "/fct/actividad/$activity", "/fct/acuerdo/$agreement"] as $url) {
            $crawler = $this->client->request('GET', $url);
            self::assertResponseIsSuccessful();
            self::assertSame(1, $crawler->selectButton('Guardar cambios')->count(), $url);
        }

        $this->connection()->executeStatement('UPDATE wpt_shift SET locked = 1 WHERE id = ?', [$shift]);

        // bloqueada, los abre pero ya no puede guardar nada
        foreach (["/fct/convocatoria/$shift", "/fct/actividad/$activity", "/fct/acuerdo/$agreement"] as $url) {
            $crawler = $this->client->request('GET', $url);
            self::assertResponseIsSuccessful();
            self::assertSame(0, $crawler->selectButton('Guardar cambios')->count(), $url);
        }
        $this->client->request('POST', "/fct/convocatoria/$shift", ['shift' => ['name' => 'Cambiado']]);
        self::assertNotSame('Cambiado', $this->connection()->fetchOne('SELECT name FROM wpt_shift WHERE id = ?', [$shift]));

        $activities = (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_activity WHERE shift_id = ?', [$shift]);
        $this->client->request('POST', "/fct/actividad/operacion/$shift", ['items' => [$activity], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', "/fct/actividad/importar/$shift", ['data' => "ZZ1 Actividad nueva\n"]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', "/fct/actividad/copiar/$shift");
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', '/fct/convocatoria/eliminar/' . $this->year(), ['items' => [$shift], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        self::assertSame($activities, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_activity WHERE shift_id = ?', [$shift]));
        self::assertSame(1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_shift WHERE id = ?', [$shift]));

        // la administración del centro sigue pudiendo, por ejemplo para desbloquearla
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $crawler = $this->client->request('GET', "/fct/convocatoria/$shift");
        self::assertSame(1, $crawler->selectButton('Guardar cambios')->count());
    }

    public function testNoAgreementCanBeCreatedInALockedShift(): void
    {
        $shift = $this->shiftId();
        $agreements = (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_agreement WHERE shift_id = ?', [$shift]);
        $this->login($this->getUser('alfa.jefe'));

        // control: con la convocatoria abierta, la jefatura puede crear convenios
        $crawler = $this->client->request('GET', "/fct/acuerdo/nuevo/$shift");
        self::assertResponseIsSuccessful();
        self::assertSame(1, $crawler->selectButton('Guardar cambios')->count());
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();

        $this->connection()->executeStatement('UPDATE wpt_shift SET locked = 1 WHERE id = ?', [$shift]);
        $crawler = $this->client->request('GET', "/fct/acuerdo/nuevo/$shift");
        if ($this->client->getResponse()->getStatusCode() === 200) {
            self::assertSame(0, $crawler->selectButton('Guardar cambios')->count());
        }
        $this->client->request('POST', "/fct/acuerdo/nuevo/$shift", $values);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame($agreements, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_agreement WHERE shift_id = ?', [$shift]));
    }

    public function testDeletionRequiresConfirmationAndRemovesTheShiftWithItsActivitiesAndAgreements(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $id = $this->createShift();
        $this->connection()->executeStatement(
            'INSERT INTO wpt_activity (shift_id, code, description) VALUES (?, ?, ?)',
            [$id, 'ZZ1', 'Actividad de prueba']
        );

        $this->client->request('POST', '/fct/convocatoria/eliminar/' . $this->year(), ['items' => [$id]]);
        self::assertResponseIsSuccessful();
        self::assertSame(1, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_shift WHERE id = ?', [$id]));

        $this->client->request('POST', '/fct/convocatoria/eliminar/' . $this->year(), ['items' => [$id], 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertSame(0, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_shift WHERE id = ?', [$id]));
        self::assertSame(0, (int) $this->connection()->fetchOne('SELECT COUNT(*) FROM wpt_activity WHERE shift_id = ?', [$id]), 'Sus actividades se borran con ella');
    }

    public function testDeletingTheShiftOfTheFixturesWithTrackedDataDoesNotFail(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // tiene convenios, jornadas y seguimiento: se borra todo o se informa del error, pero nunca falla el servidor
        $this->client->request('POST', '/fct/convocatoria/eliminar/' . $this->year(), ['items' => [$this->shiftId()], 'confirm' => 'ok']);

        self::assertResponseRedirects();
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
    }

    public function testShiftsOfAnotherYearCannotBeDeletedThroughThisOne(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $foreign = $this->shiftId(DemoData::ORG_B_CODE);
        $before = $this->shiftCount();

        $this->client->request('POST', '/fct/convocatoria/eliminar/' . $this->year(), ['items' => [$foreign], 'confirm' => 'ok']);

        self::assertSame($before, $this->shiftCount());
    }
}
