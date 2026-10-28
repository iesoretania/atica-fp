<?php

namespace App\Tests\Functional;

use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Shift;
use App\Entity\WptModule\WorkDay;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Convenios de FCT: listado, formularios, borrado, copia del calendario y descarga del programa formativo.
 *  - convenio 1: alumno1 y alumno2; convenio 2: alumno3
 */
class WptAgreementTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function shiftId(string $code = DemoData::ORG_A_CODE): int
    {
        return $this->em()->getRepository(Shift::class)->createQueryBuilder('s')
            ->join('s.subject', 'su')->join('su.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization($code))
            ->setMaxResults(1)->getQuery()->getSingleResult()->getId();
    }

    /**
     * @return list<int>
     */
    private function agreementIds(int $shiftId): array
    {
        return array_map(
            static fn(Agreement $a): int => $a->getId(),
            $this->em()->getRepository(Agreement::class)->findBy(['shift' => $shiftId], ['id' => 'ASC'])
        );
    }

    private function enrollmentId(string $username): int
    {
        return $this->em()->getRepository(AgreementEnrollment::class)->createQueryBuilder('ae')
            ->join('ae.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult()->getId();
    }

    private function workDayCount(int $agreementId): int
    {
        return $this->em()->getRepository(WorkDay::class)->count(['agreement' => $agreementId]);
    }

    /**
     * @return list<string> pantallas de lectura del convenio
     */
    private function readUrls(int $shiftId, int $agreementId, int $enrollmentId): array
    {
        return [
            "/fct/acuerdo/$shiftId/listar",
            "/fct/acuerdo/nuevo/$shiftId",
            "/fct/acuerdo/$agreementId",
            "/fct/acuerdo/estudiante/$enrollmentId",
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
        $shift = $this->shiftId();
        $agreements = $this->agreementIds($shift);
        $this->login($this->getUser($username));

        foreach ($this->readUrls($shift, $agreements[0], $this->enrollmentId('alfa.alumno1')) as $url) {
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
    public function testOthersCannotSeeOrChangeAgreements(string $username): void
    {
        $shift = $this->shiftId();
        $agreements = $this->agreementIds($shift);
        $enrollment = $this->enrollmentId('alfa.alumno1');
        $this->login($this->getUser($username));

        foreach ($this->readUrls($shift, $agreements[0], $enrollment) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/fct/acuerdo/operacion/$shift", ['items' => $agreements, 'delete' => '', 'confirm' => 'ok']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', "/fct/acuerdo/programa/descargar/$enrollment");
        self::assertResponseStatusCodeSame(403);

        self::assertSame($agreements, $this->agreementIds($shift));
    }

    public function testAdministratorOfAnotherOrganizationCannotUseThem(): void
    {
        $shift = $this->shiftId();
        $agreements = $this->agreementIds($shift);
        $enrollment = $this->enrollmentId('alfa.alumno1');
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($this->readUrls($shift, $agreements[0], $enrollment) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', "/fct/acuerdo/operacion/$shift", ['items' => $agreements, 'delete' => '', 'confirm' => 'ok']);
        self::assertSame($agreements, $this->agreementIds($shift));
    }

    public function testManagerEditsTheDefaultSchedule(): void
    {
        $shift = $this->shiftId();
        $agreement = $this->agreementIds($shift)[0];
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('GET', "/fct/acuerdo/$agreement");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'agreement[defaultStartTime1]' => '09:00',
            'agreement[defaultEndTime1]' => '15:00',
        ]));

        self::assertResponseRedirects("/fct/acuerdo/$shift/listar");
        $saved = $this->em()->getRepository(Agreement::class)->find($agreement);
        self::assertSame('09:00', $saved->getDefaultStartTime1());
        self::assertSame('15:00', $saved->getDefaultEndTime1());
    }

    // --- Borrado ---------------------------------------------------------------------------------

    public function testAgreementsAreDeletedOnlyWithConfirmation(): void
    {
        $shift = $this->shiftId();
        $agreements = $this->agreementIds($shift);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/fct/acuerdo/operacion/$shift", ['items' => [$agreements[1]], 'delete' => '']);
        self::assertResponseIsSuccessful();
        self::assertSame($agreements, $this->agreementIds($shift));

        $this->client->request('POST', "/fct/acuerdo/operacion/$shift", ['items' => [$agreements[1]], 'delete' => '', 'confirm' => 'ok']);
        self::assertResponseRedirects("/fct/acuerdo/$shift/listar");
        self::assertSame([$agreements[0]], $this->agreementIds($shift));
    }

    public function testDeletingUnknownAgreementsDoesNotFail(): void
    {
        $shift = $this->shiftId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        // identificadores que no existen o que son de otra fase: nada que mostrar, pero ningún error del servidor
        $this->client->request('POST', "/fct/acuerdo/operacion/$shift", ['items' => [999999], 'delete' => '']);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    public function testAgreementsOfAnotherShiftCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->shiftId();
        $foreign = $this->shiftId(DemoData::ORG_B_CODE);
        $foreignAgreements = $this->agreementIds($foreign);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('POST', "/fct/acuerdo/operacion/$own", ['items' => $foreignAgreements, 'delete' => '', 'confirm' => 'ok']);

        self::assertSame($foreignAgreements, $this->agreementIds($foreign));
    }

    // --- Copia del calendario ---------------------------------------------------------------------

    public function testCalendarCanBeCopiedFromAnotherAgreement(): void
    {
        $shift = $this->shiftId();
        [$first, $second] = $this->agreementIds($shift);
        $sourceDays = $this->workDayCount($second);
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $crawler = $this->client->request('POST', "/fct/acuerdo/operacion/$shift", ['items' => [$first], 'copy' => '']);
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Realizar copia')->form([
            'calendar_copy[agreement]' => (string) $second,
        ]));

        self::assertResponseRedirects("/fct/acuerdo/$shift/listar");
        self::assertSame($sourceDays, $this->workDayCount($first), 'El convenio copia las jornadas del origen');
        self::assertSame($sourceDays, $this->workDayCount($second), 'El origen no cambia');
    }

    // --- Programa formativo ------------------------------------------------------------------------

    public function testManagerDownloadsTheTrainingProgramAsPdf(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', '/fct/acuerdo/programa/descargar/' . $this->enrollmentId('alfa.alumno1'));

        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));
        self::assertStringStartsWith('%PDF-', $this->client->getInternalResponse()->getContent());
    }
}
