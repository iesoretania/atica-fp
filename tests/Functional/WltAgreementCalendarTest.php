<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\WorkDay;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Calendario de un acuerdo de FP dual (jornadas previstas): listado, edición de una jornada, incorporación de
 * jornadas a partir de un horario semanal y borrado. En FP dual las horas son horas enteras.
 *  - acuerdo 1: alumno5 (tutor docente y de grupo "tutor"); acuerdo 2: alumno6 (tutor docente "profesor")
 */
class WltAgreementCalendarTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function agreementId(int $index = 0, string $code = DemoData::ORG_A_CODE): int
    {
        return $this->em()->getRepository(Agreement::class)->createQueryBuilder('a')
            ->join('a.project', 'p')
            ->where('p.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->orderBy('a.id')->getQuery()->getResult()[$index]->getId();
    }

    /**
     * @return list<WorkDay>
     */
    private function workDays(int $agreementId): array
    {
        return $this->em()->getRepository(WorkDay::class)->findBy(['agreement' => $agreementId], ['date' => 'ASC']);
    }

    /**
     * @return list<string>
     */
    private function urls(int $agreementId, int $workDayId): array
    {
        return [
            "/dual/acuerdo/calendario/$agreementId",
            "/dual/acuerdo/calendario/$agreementId/incorporar",
            "/dual/acuerdo/calendario/jornada/$workDayId",
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'coordinador' => ['alfa.coordinador'];
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

        foreach ($this->urls($id, $workDayId) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($url);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        // el tutor docente puede gestionar el acuerdo, pero el calendario es solo del gestor de FP dual y del jefe de departamento
        yield 'tutor docente y de grupo' => ['alfa.tutor'];
        yield 'profesorado de otro acuerdo' => ['alfa.profesor'];
        yield 'tutor laboral' => ['alfa.tutorlaboral'];
        yield 'el propio alumno' => ['alfa.alumno5'];
        yield 'alumno de FCT' => ['alfa.alumno1'];
        yield 'gestor de la FFE' => ['alfa.gestorffe'];
        yield 'responsable económico' => ['alfa.economico'];
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

        foreach ($this->urls($id, $workDayId) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/dual/acuerdo/calendario/jornada/$workDayId", ['work_day' => ['hours' => '3']]);
        $this->client->request('POST', "/dual/acuerdo/calendario/$id/eliminar", ['items' => [$workDayId], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);

        self::assertCount(count($workDays), $this->workDays($id));
        self::assertSame($hours, $this->em()->getRepository(WorkDay::class)->find($workDayId)->getHours());
    }

    public function testAdministratorOfAnotherOrganizationCannotUseTheCalendar(): void
    {
        $id = $this->agreementId();
        $workDays = $this->workDays($id);
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($this->urls($id, $workDays[0]->getId()) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/dual/acuerdo/calendario/$id/eliminar", ['items' => [$workDays[0]->getId()], 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        self::assertCount(count($workDays), $this->workDays($id));
    }

    public function testManagerChangesTheHoursOfAWorkDay(): void
    {
        $id = $this->agreementId();
        $workDayId = $this->workDays($id)[0]->getId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/dual/acuerdo/calendario/jornada/$workDayId");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['work_day[hours]' => '7']));

        self::assertResponseRedirects("/dual/acuerdo/calendario/$id");
        self::assertSame(7, $this->em()->getRepository(WorkDay::class)->find($workDayId)->getHours());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidHoursProvider(): iterable
    {
        yield 'negativas' => ['-3'];
        yield 'más de 24 horas' => ['25'];
        yield 'decimales' => ['7.5'];
        yield 'texto' => ['muchas'];
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

        $this->client->request('GET', '/dual/acuerdo/calendario/jornada/' . $workDay->getId());
        $crawler = $this->client->getCrawler();
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        $values['work_day']['hours'] = $hours;
        $this->client->request('POST', '/dual/acuerdo/calendario/jornada/' . $workDay->getId(), $values);

        self::assertResponseStatusCodeSame(200);
        self::assertSame($original, $this->em()->getRepository(WorkDay::class)->find($workDay->getId())->getHours());
    }

    public function testTwentyFourHoursIsTheMaximumAllowed(): void
    {
        $id = $this->agreementId();
        $workDayId = $this->workDays($id)[0]->getId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/dual/acuerdo/calendario/jornada/$workDayId");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['work_day[hours]' => '24']));

        self::assertResponseRedirects("/dual/acuerdo/calendario/$id");
        self::assertSame(24, $this->em()->getRepository(WorkDay::class)->find($workDayId)->getHours());
    }

    public function testWorkDaysAreDeletedOnlyWithConfirmation(): void
    {
        $id = $this->agreementId();
        $workDays = $this->workDays($id);
        $items = [$workDays[0]->getId(), $workDays[1]->getId()];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/dual/acuerdo/calendario/$id/eliminar", ['items' => $items]);
        self::assertResponseIsSuccessful();
        self::assertCount(count($workDays), $this->workDays($id));

        $this->client->request('POST', "/dual/acuerdo/calendario/$id/eliminar", ['items' => $items, 'confirm' => 'ok']);
        self::assertResponseRedirects("/dual/acuerdo/calendario/$id");
        self::assertCount(count($workDays) - 2, $this->workDays($id));
    }

    public function testWorkDaysOfAnotherAgreementCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->agreementId(0);
        $other = $this->agreementId(1);
        $otherDays = $this->workDays($other);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/dual/acuerdo/calendario/$own/eliminar", ['items' => [$otherDays[0]->getId()], 'confirm' => 'ok']);

        self::assertCount(count($otherDays), $this->workDays($other));
    }

    private function emptyCalendar(int $agreementId): void
    {
        $this->client->request('POST', "/dual/acuerdo/calendario/$agreementId/eliminar", [
            'items' => array_map(static fn(WorkDay $w): int => $w->getId(), $this->workDays($agreementId)),
            'confirm' => 'ok',
        ]);
        self::assertCount(0, $this->workDays($agreementId));
    }

    private function addForm(int $agreementId, string $button): void
    {
        $crawler = $this->client->request('GET', "/dual/acuerdo/calendario/$agreementId/incorporar");
        $this->client->submit($crawler->selectButton($button)->form([
            'calendar_add[startDate]' => DemoData::currentSchoolYearStart()->format('Y-m-d'),
            'calendar_add[totalHours]' => '40',
            'calendar_add[hoursMon]' => '8',
            'calendar_add[hoursTue]' => '8',
            'calendar_add[hoursWed]' => '8',
            'calendar_add[hoursThu]' => '8',
            'calendar_add[hoursFri]' => '8',
            'calendar_add[hoursSat]' => '0',
            'calendar_add[hoursSun]' => '0',
        ]));
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

        self::assertResponseRedirects("/dual/acuerdo/calendario/$id");
        $workDays = $this->workDays($id);
        self::assertNotEmpty($workDays);
        self::assertSame(40, array_sum(array_map(static fn(WorkDay $w): int => $w->getHours(), $workDays)));
        foreach ($workDays as $workDay) {
            self::assertLessThan(6, (int) $workDay->getDate()->format('N'), 'No se programan fines de semana');
        }
        $agreement = $this->em()->getRepository(Agreement::class)->find($id);
        self::assertSame($workDays[0]->getDate()->format('Y-m-d'), $agreement->getStartDate()->format('Y-m-d'));
        self::assertSame(end($workDays)->getDate()->format('Y-m-d'), $agreement->getEndDate()->format('Y-m-d'));
    }
}
