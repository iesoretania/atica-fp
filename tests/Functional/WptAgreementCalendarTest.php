<?php

namespace App\Tests\Functional;

use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\WorkDay;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Calendario de un convenio de FCT (jornadas previstas): listado, edición de una jornada, incorporación de
 * jornadas a partir de un horario semanal y borrado. Las horas se guardan en centésimas.
 *  - convenio 1: alumno1 y alumno2; convenio 2: alumno3
 */
class WptAgreementCalendarTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    /**
     * @return list<Agreement>
     */
    private function agreements(string $code = DemoData::ORG_A_CODE): array
    {
        return $this->em()->getRepository(Agreement::class)->createQueryBuilder('a')
            ->join('a.shift', 's')->join('s.subject', 'su')->join('su.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->orderBy('a.id')->getQuery()->getResult();
    }

    private function agreementId(int $index = 0, string $code = DemoData::ORG_A_CODE): int
    {
        return $this->agreements($code)[$index]->getId();
    }

    /**
     * @return list<WorkDay>
     */
    private function workDays(int $agreementId): array
    {
        return $this->em()->getRepository(WorkDay::class)->findBy(['agreement' => $agreementId], ['date' => 'ASC']);
    }

    // --- Permisos ----------------------------------------------------------------------------------

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
    public function testManagersSeeTheCalendarAndTheEditionForms(string $username): void
    {
        $id = $this->agreementId();
        $workDayId = $this->workDays($id)[0]->getId();
        $this->login($this->getUser($username));

        foreach (["/fct/convenio/calendario/$id", "/fct/convenio/calendario/$id/incorporar", "/fct/convenio/calendario/jornada/$workDayId"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
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
    public function testOthersCannotSeeOrChangeTheCalendar(string $username): void
    {
        $id = $this->agreementId();
        $workDays = $this->workDays($id);
        $workDayId = $workDays[0]->getId();
        $hours = $workDays[0]->getHours();
        $this->login($this->getUser($username));

        foreach (["/fct/convenio/calendario/$id", "/fct/convenio/calendario/$id/incorporar", "/fct/convenio/calendario/jornada/$workDayId"] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/fct/convenio/calendario/jornada/$workDayId", ['work_day' => ['hours' => '3']]);
        $this->client->request('POST', "/fct/convenio/calendario/$id/eliminar", ['items' => [$workDayId], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertCount(count($workDays), $this->workDays($id));
        self::assertSame($hours, $this->em()->getRepository(WorkDay::class)->find($workDayId)->getHours());
    }

    public function testAdministratorOfAnotherOrganizationCannotUseTheCalendar(): void
    {
        $id = $this->agreementId();
        $workDays = $this->workDays($id);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (["/fct/convenio/calendario/$id", "/fct/convenio/calendario/$id/incorporar", '/fct/convenio/calendario/jornada/' . $workDays[0]->getId()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/fct/convenio/calendario/$id/eliminar", ['items' => [$workDays[0]->getId()], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        self::assertCount(count($workDays), $this->workDays($id));
    }

    // --- Edición de una jornada --------------------------------------------------------------------

    public function testManagerChangesTheHoursOfAWorkDay(): void
    {
        $id = $this->agreementId();
        $workDayId = $this->workDays($id)[0]->getId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/fct/convenio/calendario/jornada/$workDayId");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['work_day[hours]' => '7.5']));

        self::assertResponseRedirects("/fct/convenio/calendario/$id");
        self::assertSame(750, $this->em()->getRepository(WorkDay::class)->find($workDayId)->getHours());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidHoursProvider(): iterable
    {
        yield 'cero' => ['0'];
        yield 'negativas' => ['-3'];
        yield 'más de 24 horas' => ['25'];
    }

    /**
     * @dataProvider invalidHoursProvider
     */
    public function testInvalidHoursAreRejected(string $hours): void
    {
        $id = $this->agreementId();
        $workDay = $this->workDays($id)[0];
        $original = $workDay->getHours();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', '/fct/convenio/calendario/jornada/' . $workDay->getId());
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['work_day[hours]' => $hours]));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($original, $this->em()->getRepository(WorkDay::class)->find($workDay->getId())->getHours());
    }

    // --- Borrado -------------------------------------------------------------------------------------

    public function testWorkDaysAreDeletedOnlyWithConfirmation(): void
    {
        $id = $this->agreementId();
        $workDays = $this->workDays($id);
        $items = [$workDays[0]->getId(), $workDays[1]->getId()];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/fct/convenio/calendario/$id/eliminar", ['items' => $items]);
        self::assertResponseIsSuccessful();
        self::assertCount(count($workDays), $this->workDays($id));

        $this->client->request('POST', "/fct/convenio/calendario/$id/eliminar", ['items' => $items, 'confirm' => 'ok']);
        self::assertResponseRedirects("/fct/convenio/calendario/$id");
        self::assertCount(count($workDays) - 2, $this->workDays($id));
    }

    public function testWorkDaysOfAnotherAgreementCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->agreementId(0);
        $other = $this->agreementId(1);
        $otherDays = $this->workDays($other);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/fct/convenio/calendario/$own/eliminar", ['items' => [$otherDays[0]->getId()], 'confirm' => 'ok']);

        self::assertCount(count($otherDays), $this->workDays($other));
    }

    // --- Incorporación de jornadas -------------------------------------------------------------------

    private function emptyCalendar(int $agreementId): void
    {
        $workDays = $this->workDays($agreementId);
        $this->client->request('POST', "/fct/convenio/calendario/$agreementId/eliminar", [
            'items' => array_map(static fn(WorkDay $w): int => $w->getId(), $workDays),
            'confirm' => 'ok',
        ]);
        self::assertCount(0, $this->workDays($agreementId));
    }

    private function addForm(int $agreementId, string $button): void
    {
        $start = DemoData::currentSchoolYearStart();
        $crawler = $this->client->request('GET', "/fct/convenio/calendario/$agreementId/incorporar");
        $form = $crawler->selectButton($button)->form([
            'calendar_add[startDate]' => $start->format('Y-m-d'),
            'calendar_add[totalHours]' => '40',
            'calendar_add[hoursMon]' => '8',
            'calendar_add[hoursTue]' => '8',
            'calendar_add[hoursWed]' => '8',
            'calendar_add[hoursThu]' => '8',
            'calendar_add[hoursFri]' => '8',
            'calendar_add[hoursSat]' => '0',
            'calendar_add[hoursSun]' => '0',
        ]);
        $this->client->submit($form);
    }

    public function testPreviewDoesNotSaveAnything(): void
    {
        $id = $this->agreementId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->emptyCalendar($id);

        $this->addForm($id, 'Vista previa');

        self::assertResponseIsSuccessful();
        self::assertCount(0, $this->workDays($id));
    }

    public function testManagerAddsWorkDaysFromAWeeklySchedule(): void
    {
        $id = $this->agreementId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->emptyCalendar($id);

        $this->addForm($id, 'Añadir jornadas');

        self::assertResponseRedirects("/fct/convenio/calendario/$id");
        $workDays = $this->workDays($id);
        self::assertNotEmpty($workDays);
        // 40 horas repartidas en jornadas de 8 horas (en centésimas), sin pasarse del total
        self::assertSame(4000, array_sum(array_map(static fn(WorkDay $w): int => $w->getHours(), $workDays)));
        foreach ($workDays as $workDay) {
            self::assertLessThan(6, (int) $workDay->getDate()->format('N'), 'No se programan fines de semana');
        }
        // las fechas del convenio se actualizan con el calendario
        $agreement = $this->em()->getRepository(Agreement::class)->find($id);
        self::assertSame($workDays[0]->getDate()->format('Y-m-d'), $agreement->getStartDate()->format('Y-m-d'));
        self::assertSame(end($workDays)->getDate()->format('Y-m-d'), $agreement->getEndDate()->format('Y-m-d'));
    }
}
