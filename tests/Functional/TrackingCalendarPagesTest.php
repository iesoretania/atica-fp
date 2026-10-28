<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\WptModule\AgreementEnrollment;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Pantalla del calendario de seguimiento diario, su API y los informes en PDF de asistencia y actividades,
 * en FCT (convenio 1: alumno1, tutor "tutor", tutor laboral "tutorlaboral") y en la FFE (estancia de alumno7,
 * tutor docente "profesor", tutor laboral "tutorlaboral", gestor "gestorffe").
 */
class TrackingCalendarPagesTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function wptEnrollmentId(string $username = 'alfa.alumno1'): int
    {
        return $this->em()->getRepository(AgreementEnrollment::class)->createQueryBuilder('ae')
            ->join('ae.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult()->getId();
    }

    private function itpStayId(string $username = 'alfa.alumno7'): int
    {
        return $this->em()->getRepository(StudentProgramWorkcenter::class)->createQueryBuilder('s')
            ->join('s.studentProgram', 'sp')->join('sp.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult()->getId();
    }

    /**
     * @return array<string, string> pantallas y descargas de cada módulo, por nombre
     */
    protected function urls(): array
    {
        $enrollment = $this->wptEnrollmentId();
        $stay = $this->itpStayId();
        $date = new \DateTimeImmutable('today');
        $year = $date->format('o');
        $week = $date->format('W');

        return [
            'FCT calendario' => "/fct/seguimiento/calendario/$enrollment",
            'FCT API' => "/fct/seguimiento/calendario/api/v2/$enrollment",
            'FCT asistencia' => "/fct/seguimiento/calendario/$enrollment/asistencia/descargar",
            'FCT resumen de actividades' => "/fct/seguimiento/calendario/$enrollment/actividades/descargar",
            'FCT informe semanal' => "/fct/seguimiento/calendario/$enrollment/informe/$year/$week",
            'FFE calendario' => "/formacion/seguimiento/calendario/$stay",
            'FFE asistencia' => "/formacion/seguimiento/calendario/$stay/asistencia",
            'FFE informe semanal' => "/formacion/seguimiento/calendario/informe/descargar/$stay/$year/$week",
        ];
    }

    /**
     * @return iterable<string, array{string, list<string>}> usuario y pantallas del módulo que puede abrir
     */
    public static function participantProvider(): iterable
    {
        $fct = ['FCT calendario', 'FCT API', 'FCT asistencia', 'FCT resumen de actividades', 'FCT informe semanal'];
        $ffe = ['FFE calendario', 'FFE asistencia', 'FFE informe semanal'];

        // el alumnado y el tutor laboral descargan los partes de su calendario, pero no el resumen de actividades
        yield 'alumno de FCT' => ['alfa.alumno1', ['FCT calendario', 'FCT API', 'FCT asistencia', 'FCT informe semanal']];
        yield 'tutor laboral' => ['alfa.tutorlaboral', ['FCT calendario', 'FCT API', 'FCT asistencia', 'FCT informe semanal', ...$ffe]];
        yield 'alumno de FFE' => ['alfa.alumno7', $ffe];
        // tutor docente y de grupo: ve el convenio de FCT y las estancias de su grupo en la FFE
        yield 'tutor de grupo' => ['alfa.tutor', [...$fct, ...$ffe]];
        yield 'tutor docente de FFE' => ['alfa.profesor', $ffe];
        yield 'gestor de la FFE' => ['alfa.gestorffe', $ffe];
        yield 'administrador local' => ['alfa.admin', [...$fct, ...$ffe]];
        yield 'jefe de departamento' => ['alfa.jefe', [...$fct, ...$ffe]];
    }

    /**
     * @dataProvider participantProvider
     *
     * @param list<string> $allowed
     */
    public function testParticipantsOpenOnlyWhatTheyAreAllowedTo(string $username, array $allowed): void
    {
        $urls = $this->urls();
        $this->login($this->getUser($username));

        foreach ($urls as $name => $url) {
            $this->client->request('GET', $url);
            $status = $this->client->getResponse()->getStatusCode();
            if (in_array($name, $allowed, true)) {
                self::assertLessThan(400, $status, "$username debería abrir $name (HTTP $status)");
            } else {
                self::assertSame(403, $status, "$username no debería abrir $name (HTTP $status)");
            }
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        yield 'otro alumno de FCT' => ['alfa.alumno3'];
        yield 'alumno de FP dual' => ['alfa.alumno5'];
        yield 'tutor laboral de otro convenio' => ['alfa.tutorlaboral2'];
        yield 'coordinador de FP dual' => ['alfa.coordinador'];
        yield 'responsable económico' => ['alfa.economico'];
        yield 'administrador local de otro centro' => ['beta.admin'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOutsidersCannotOpenAnyScreen(string $username): void
    {
        $urls = $this->urls();
        $this->login($this->getUser($username), true, str_starts_with($username, 'beta.') ? DemoData::ORG_B_CODE : DemoData::ORG_A_CODE);

        foreach ($urls as $name => $url) {
            $this->client->request('GET', $url);
            self::assertSame(403, $this->client->getResponse()->getStatusCode(), "$username: $name");
        }
    }

    public function testReportsAreRealPdfFiles(): void
    {
        $urls = $this->urls();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach (['FCT asistencia', 'FCT resumen de actividades', 'FCT informe semanal', 'FFE asistencia', 'FFE informe semanal'] as $name) {
            $this->client->request('GET', $urls[$name]);
            self::assertResponseIsSuccessful($name);
            self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'), $name);
            self::assertStringStartsWith('%PDF-', $this->client->getInternalResponse()->getContent(), $name);
        }
    }

    public function testApiReturnsTheCalendarWithoutPersonalData(): void
    {
        $urls = $this->urls();
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('GET', $urls['FCT API']);

        self::assertResponseIsSuccessful();
        $json = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        foreach (['selectable', 'activity_stats', 'activity_tracked_count', 'activity_total_count', 'activity_total_hours', 'calendar', 'read_only'] as $key) {
            self::assertArrayHasKey($key, $json);
        }
        // el alumno puede rellenar sus jornadas pero no bloquearlas
        self::assertTrue($json['read_only']);
        self::assertNotEmpty($json['calendar']);
        self::assertStringNotContainsString('$2y$', (string) $this->client->getResponse()->getContent());
    }

    public function testApiTellsTheTutorTheyCanLockDays(): void
    {
        $urls = $this->urls();
        $this->login($this->getUser('alfa.tutor'));

        $this->client->request('GET', $urls['FCT API']);

        $json = json_decode((string) $this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($json['read_only']);
        self::assertTrue($json['selectable']);
    }

    public function testReportOfAFutureOrInvalidWeekDoesNotFail(): void
    {
        $enrollment = $this->wptEnrollmentId();
        $stay = $this->itpStayId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach ([
            "/fct/seguimiento/calendario/$enrollment/informe/2030/10",
            "/fct/seguimiento/calendario/$enrollment/informe/1900/1",
            "/fct/seguimiento/calendario/$enrollment/informe/2026/99",
            "/fct/seguimiento/calendario/$enrollment/informe/abc/xyz",
            "/formacion/seguimiento/calendario/informe/descargar/$stay/2030/10",
            "/formacion/seguimiento/calendario/informe/descargar/$stay/2026/99",
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), $url);
        }
    }
}
