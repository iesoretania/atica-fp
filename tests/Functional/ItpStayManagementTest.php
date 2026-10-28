<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\WorkDay;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Gestión de las estancias de la FFE y de su calendario de jornadas previstas, tanto desde la pantalla del
 * alumno como desde la del grupo. Solo el jefe de departamento y el gestor del grupo pueden gestionarlas.
 *  - alumno7 y alumno8: una estancia cada uno; "gestorffe" gestiona el grupo de ambos. Horas en centésimas.
 */
class ItpStayManagementTest extends AppWebTestCase
{
    private const STUDENT = '/formacion/plan/curso/grupo/estudiante/estancia';
    private const MANAGER = '/formacion/plan/curso/grupo/gestion';

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function stay(string $username = 'alfa.alumno7'): StudentProgramWorkcenter
    {
        return $this->em()->getRepository(StudentProgramWorkcenter::class)->createQueryBuilder('s')
            ->join('s.studentProgram', 'sp')->join('sp.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult();
    }

    /**
     * @return array{stay: int, program: int, group: int, grade: int, workDay: int}
     */
    private function ids(string $username = 'alfa.alumno7'): array
    {
        $stay = $this->stay($username);
        $program = $stay->getStudentProgram();

        return [
            'stay' => $stay->getId(),
            'program' => $program->getId(),
            'group' => $program->getProgramGroup()->getId(),
            'grade' => $program->getProgramGroup()->getProgramGrade()->getId(),
            'workDay' => $this->workDays($stay->getId())[0]->getId(),
        ];
    }

    /**
     * @return list<WorkDay>
     */
    private function workDays(int $stayId): array
    {
        return $this->em()->getRepository(WorkDay::class)->findBy(['studentProgramWorkcenter' => $stayId], ['date' => 'ASC']);
    }

    /**
     * Pantallas de lectura de la gestión de estancias y de su calendario
     *
     * @return list<string>
     */
    private function readUrls(array $ids): array
    {
        return [
            self::STUDENT . "/listar/{$ids['program']}",
            self::STUDENT . "/nueva/{$ids['program']}",
            self::STUDENT . "/{$ids['stay']}",
            self::STUDENT . "/calendario/{$ids['stay']}",
            self::STUDENT . "/calendario/incorporar/{$ids['stay']}",
            self::STUDENT . "/calendario/jornada/{$ids['workDay']}",
            self::MANAGER . "/listar/{$ids['grade']}",
            self::MANAGER . "/nueva/{$ids['group']}",
            self::MANAGER . "/detalle/{$ids['stay']}",
            self::MANAGER . "/estancia/calendario/{$ids['stay']}",
            self::MANAGER . "/estancia/calendario/incorporar/{$ids['stay']}",
            self::MANAGER . "/estancia/calendario/jornada/{$ids['workDay']}",
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'gestor de la FFE' => ['alfa.gestorffe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenEveryScreen(string $username): void
    {
        $ids = $this->ids();
        $this->login($this->getUser($username));

        foreach ($this->readUrls($ids) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful($username . ' ' . $url);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['profesor', 'tutor', 'tutorlaboral', 'alumno7', 'coordinador', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
        yield 'gestor de la FFE de otro centro' => ['beta.gestorffe'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotOpenOrChangeAnything(string $username): void
    {
        $ids = $this->ids();
        $days = count($this->workDays($ids['stay']));
        $hours = $this->workDays($ids['stay'])[0]->getHours();
        $this->login($this->getUser($username), true, str_starts_with($username, 'beta.') ? DemoData::ORG_B_CODE : DemoData::ORG_A_CODE);

        foreach ($this->readUrls($ids) as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $url);
        }
        foreach ([
            self::STUDENT . "/eliminar/{$ids['program']}",
            self::STUDENT . "/calendario/eliminar/{$ids['stay']}",
            self::MANAGER . "/operacion/{$ids['grade']}",
            self::MANAGER . "/estancia/calendario/eliminar/{$ids['stay']}",
        ] as $url) {
            $this->client->request('POST', $url, ['items' => [$ids['stay'], $ids['workDay']], 'confirm' => 'ok']);
            self::assertResponseStatusCodeSame(403, $url);
        }
        $this->client->request('POST', self::STUDENT . "/calendario/jornada/{$ids['workDay']}", ['work_day' => ['hours' => '3']]);

        self::assertCount($days, $this->workDays($ids['stay']));
        self::assertSame($hours, $this->workDays($ids['stay'])[0]->getHours());
        self::assertSame($ids['stay'], $this->stay()->getId());
    }

    // --- Calendario ---------------------------------------------------------------------------------

    /**
     * @return iterable<string, array{string}>
     */
    public static function calendarRouteProvider(): iterable
    {
        yield 'desde el alumno' => [self::STUDENT];
        yield 'desde el grupo' => [self::MANAGER . '/estancia'];
    }

    /**
     * @dataProvider calendarRouteProvider
     */
    public function testManagerChangesTheHoursOfAWorkDay(string $prefix): void
    {
        $ids = $this->ids();
        $this->login($this->getUser('alfa.gestorffe'));

        $crawler = $this->client->request('GET', $prefix . "/calendario/jornada/{$ids['workDay']}");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['work_day[hours]' => '7.5']));

        self::assertResponseRedirects();
        self::assertSame(750, $this->em()->getRepository(WorkDay::class)->find($ids['workDay'])->getHours());
    }

    /**
     * @dataProvider calendarRouteProvider
     */
    public function testInvalidHoursAreRejected(string $prefix): void
    {
        $ids = $this->ids();
        $original = $this->workDays($ids['stay'])[0]->getHours();
        $this->login($this->getUser('alfa.gestorffe'));

        foreach (['-3', '0', '25'] as $hours) {
            $crawler = $this->client->request('GET', $prefix . "/calendario/jornada/{$ids['workDay']}");
            $this->client->submit($crawler->selectButton('Guardar cambios')->form(['work_day[hours]' => $hours]));
            self::assertResponseStatusCodeSame(200, "horas $hours");
        }

        self::assertSame($original, $this->em()->getRepository(WorkDay::class)->find($ids['workDay'])->getHours());
    }

    /**
     * @dataProvider calendarRouteProvider
     */
    public function testWorkDaysAreDeletedOnlyWithConfirmation(string $prefix): void
    {
        $ids = $this->ids();
        $days = $this->workDays($ids['stay']);
        $items = [$days[0]->getId(), $days[1]->getId()];
        $this->login($this->getUser('alfa.gestorffe'));

        $this->client->request('POST', $prefix . "/calendario/eliminar/{$ids['stay']}", ['items' => $items]);
        self::assertResponseIsSuccessful();
        self::assertCount(count($days), $this->workDays($ids['stay']));

        $this->client->request('POST', $prefix . "/calendario/eliminar/{$ids['stay']}", ['items' => $items, 'confirm' => 'ok']);
        self::assertResponseRedirects();
        self::assertCount(count($days) - 2, $this->workDays($ids['stay']));
    }

    /**
     * @dataProvider calendarRouteProvider
     */
    public function testWorkDaysOfAnotherStayCannotBeDeletedThroughThisOne(string $prefix): void
    {
        $own = $this->ids('alfa.alumno7');
        $other = $this->ids('alfa.alumno8');
        $otherDays = count($this->workDays($other['stay']));
        $this->login($this->getUser('alfa.gestorffe'));

        $this->client->request('POST', $prefix . "/calendario/eliminar/{$own['stay']}", ['items' => [$other['workDay']], 'confirm' => 'ok']);

        self::assertCount($otherDays, $this->workDays($other['stay']));
    }

    // --- Estancias ----------------------------------------------------------------------------------

    public function testStaysOfAnotherStudentCannotBeDeletedThroughThisOne(): void
    {
        $own = $this->ids('alfa.alumno7');
        $other = $this->ids('alfa.alumno8');
        $this->login($this->getUser('alfa.gestorffe'));

        $this->client->request('POST', self::STUDENT . "/eliminar/{$own['program']}", ['items' => [$other['stay']], 'confirm' => 'ok']);

        self::assertSame($other['stay'], $this->stay('alfa.alumno8')->getId());
    }

    public function testStayWithGradingCommentsCanBeDeleted(): void
    {
        $ids = $this->ids('alfa.alumno7');
        $this->login($this->getUser('alfa.gestorffe'));

        // la estancia de las fixtures tiene valoraciones y comentarios de actividades
        $this->client->request('POST', self::STUDENT . "/eliminar/{$ids['program']}", ['items' => [$ids['stay']], 'confirm' => 'ok']);

        self::assertResponseRedirects();
        self::assertNull($this->em()->getRepository(StudentProgramWorkcenter::class)->find($ids['stay']));
    }

    public function testStayDeletionRequiresConfirmation(): void
    {
        $ids = $this->ids();
        $this->login($this->getUser('alfa.gestorffe'));

        $this->client->request('POST', self::STUDENT . "/eliminar/{$ids['program']}", ['items' => [$ids['stay']]]);
        self::assertResponseIsSuccessful();
        self::assertSame($ids['stay'], $this->stay()->getId());
    }
}
