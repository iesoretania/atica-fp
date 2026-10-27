<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\WorkDay;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Seguimiento diario de la FFE (calendario del alumnado en el centro de trabajo): quién puede rellenar,
 * bloquear y marcar faltas. Las estancias de las fixtures son:
 *  - alumno7: tutor docente "profesor", tutor laboral "tutorlaboral"
 *  - alumno8: tutor docente "tutor", tutor laboral "tutorlaboral2"
 * El gestor de la FFE ("gestorffe") gestiona el grupo de ambos. Las horas se guardan en centésimas.
 */
class ItpTrackingCalendarTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function router(): \Symfony\Component\Routing\RouterInterface
    {
        return static::getContainer()->get('router');
    }

    private function stayOf(string $username): StudentProgramWorkcenter
    {
        return $this->em()->getRepository(StudentProgramWorkcenter::class)->createQueryBuilder('s')
            ->join('s.studentProgram', 'sp')->join('sp.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult();
    }

    /**
     * Jornada ya pasada y sin falta de la estancia
     */
    private function workDayOf(string $username): WorkDay
    {
        return $this->em()->getRepository(WorkDay::class)->createQueryBuilder('w')
            ->where('w.studentProgramWorkcenter = :stay')->andWhere('w.absence = 0')->andWhere('w.date <= :today')
            ->setParameter('stay', $this->stayOf($username))->setParameter('today', new \DateTime('today'))
            ->orderBy('w.date', 'DESC')->setMaxResults(1)->getQuery()->getSingleResult();
    }

    private function lock(WorkDay $workDay, bool $locked = true): void
    {
        $em = $this->em();
        $em->find(WorkDay::class, $workDay->getId())->setLocked($locked);
        $em->flush();
    }

    private function reload(WorkDay $workDay): WorkDay
    {
        return $this->em()->find(WorkDay::class, $workDay->getId());
    }

    private function formUrl(WorkDay $workDay): string
    {
        return $this->router()->generate('in_company_training_phase_tracking_calendar_form', ['workDay' => $workDay->getId()]);
    }

    private function operationUrl(WorkDay $workDay): string
    {
        return $this->router()->generate('in_company_training_phase_tracking_calendar_operation', [
            'id' => $workDay->getStudentProgramWorkcenter()->getId(),
        ]);
    }

    private function token(WorkDay $workDay): string
    {
        $crawler = $this->client->request('GET', $this->formUrl($workDay));

        return $crawler->filter('input[name="work_day_tracking[_token]"]')->attr('value');
    }

    public function testStudentFillsTheirOwnDay(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $this->login($this->getUser('alfa.alumno7'));

        $crawler = $this->client->request('GET', $this->formUrl($workDay));
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'work_day_tracking[notes]' => 'Notas escritas por el alumno',
            'work_day_tracking[startTime1]' => '08:00',
            'work_day_tracking[endTime1]' => '14:00',
        ]));

        self::assertResponseRedirects();
        $reloaded = $this->reload($workDay);
        self::assertSame('Notas escritas por el alumno', $reloaded->getNotes());
        self::assertSame('08:00', $reloaded->getStartTime1());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        yield 'alumno de otra estancia' => ['alfa.alumno8'];
        yield 'tutor laboral de otra estancia' => ['alfa.tutorlaboral2'];
        yield 'alumno de FCT' => ['alfa.alumno1'];
        yield 'alumno de otro centro' => ['beta.alumno7'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testPeopleOutsideTheStayCannotOpenOrChangeTheDay(string $username): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $url = $this->formUrl($workDay);
        $this->login($this->getUser($username));

        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $url, ['work_day_tracking' => ['notes' => 'Escrito por quien no debe']]);
        self::assertResponseStatusCodeSame(403);
        self::assertNotSame('Escrito por quien no debe', $this->reload($workDay)->getNotes());
    }

    public function testEditingALockedDayThroughTheFormChangesNothing(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $notes = $workDay->getNotes();
        $this->lock($workDay);
        $this->login($this->getUser('alfa.alumno7'));

        $token = $this->token($workDay);
        $this->client->request('POST', $this->formUrl($workDay), [
            'work_day_tracking' => ['notes' => 'Modificada estando bloqueada', 'startTime1' => '09:00', 'endTime1' => '10:00', '_token' => $token],
        ]);

        $reloaded = $this->reload($workDay);
        self::assertSame($notes, $reloaded->getNotes());
        self::assertNotSame('09:00', $reloaded->getStartTime1());
    }

    public function testStudentCannotUnlockTheirDayWithACraftedRequest(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $this->lock($workDay);
        $this->login($this->getUser('alfa.alumno7'));

        $token = $this->token($workDay);
        $this->client->request('POST', $this->formUrl($workDay), [
            'work_day_tracking' => ['locked' => '0', 'notes' => 'Desbloqueada por el alumno', '_token' => $token],
        ]);

        self::assertTrue($this->reload($workDay)->isLocked(), 'El alumno ha desbloqueado su propia jornada');
    }

    public function testStudentCannotMarkAnAbsenceThroughTheForm(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $this->login($this->getUser('alfa.alumno7'));

        $token = $this->token($workDay);
        $this->client->request('POST', $this->formUrl($workDay), [
            'work_day_tracking' => ['absence' => (string) WorkDay::ABSENCE_JUSTIFIED, '_token' => $token],
        ]);

        self::assertSame(WorkDay::ABSENCE_NONE, $this->reload($workDay)->getAbsence());
    }

    public function testNotesAreEscapedWhenShown(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $em = $this->em();
        $em->find(WorkDay::class, $workDay->getId())->setNotes('<script>alert("xss")</script><img src=x onerror=alert(1)>');
        $em->flush();
        $this->login($this->getUser('alfa.profesor'));

        $this->client->request('GET', $this->formUrl($workDay));
        $content = (string) $this->client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('<script>alert("xss")', $content);
        self::assertStringNotContainsString('<img src=x onerror', $content);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidTimeProvider(): iterable
    {
        yield 'texto' => ['muchas'];
        yield 'hora inexistente' => ['25:00'];
        yield 'minutos que no son de cuarto' => ['08:07'];
        yield 'con código' => ['08:00<script>'];
    }

    /**
     * @dataProvider invalidTimeProvider
     */
    public function testMalformedTimesAreRejected(string $time): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $this->login($this->getUser('alfa.alumno7'));

        $token = $this->token($workDay);
        $this->client->request('POST', $this->formUrl($workDay), [
            'work_day_tracking' => ['startTime1' => $time, 'endTime1' => '14:00', '_token' => $token],
        ]);

        self::assertNotSame($time, $this->reload($workDay)->getStartTime1(), 'Se ha guardado una hora mal formada');
    }

    /**
     * @return iterable<string, array{string, int}> usuario y estado esperado al bloquear una semana
     */
    public static function lockProvider(): iterable
    {
        yield 'el alumno no puede bloquear' => ['alfa.alumno7', 403];
        yield 'el tutor laboral no puede bloquear' => ['alfa.tutorlaboral', 403];
        yield 'el tutor de otra estancia no puede bloquear' => ['alfa.tutorlaboral2', 403];
        yield 'el tutor docente puede bloquear' => ['alfa.profesor', 302];
        yield 'el gestor de la FFE puede bloquear' => ['alfa.gestorffe', 302];
    }

    /**
     * @dataProvider lockProvider
     */
    public function testOnlyTutorsCanLockAWeek(string $username, int $expectedStatus): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $date = $workDay->getDate();
        $week = (int) $date->format('o') * 100 + (int) $date->format('W');
        $this->login($this->getUser($username));

        $this->client->request('POST', $this->operationUrl($workDay), ['lock_week' => $week]);

        self::assertResponseStatusCodeSame($expectedStatus);
        self::assertSame($expectedStatus === 302, $this->reload($workDay)->isLocked());
    }

    public function testTutorLocksAndUnlocksSelectedDaysOfTheStayOnly(): void
    {
        $mine = $this->workDayOf('alfa.alumno7');
        $other = $this->workDayOf('alfa.alumno8');
        $this->login($this->getUser('alfa.profesor'));

        // se intenta bloquear una jornada de otra estancia a través de la primera
        $this->client->request('POST', $this->operationUrl($mine), [
            'items' => [$mine->getId(), $other->getId()],
            'lock' => '',
        ]);
        self::assertResponseRedirects();
        self::assertTrue($this->reload($mine)->isLocked());
        self::assertFalse($this->reload($other)->isLocked(), 'Se ha bloqueado una jornada de otra estancia');

        $this->client->request('POST', $this->operationUrl($mine), ['items' => [$mine->getId()], 'unlock' => '']);
        self::assertFalse($this->reload($mine)->isLocked());
    }

    public function testStudentCannotLockDaysThroughTheOperation(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $this->login($this->getUser('alfa.alumno7'));

        $this->client->request('POST', $this->operationUrl($workDay), ['items' => [$workDay->getId()], 'lock' => '']);

        self::assertFalse($this->reload($workDay)->isLocked());
    }

    public function testWorkTutorMarksUnjustifiedButNotJustifiedAbsences(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $this->login($this->getUser('alfa.tutorlaboral'));

        $this->client->request('POST', $this->operationUrl($workDay), [
            'items' => [$workDay->getId()],
            'submit-unjustified' => '',
        ]);
        self::assertResponseRedirects();
        self::assertSame(WorkDay::ABSENCE_UNJUSTIFIED, $this->reload($workDay)->getAbsence());

        // justificarla es cosa de quien puede bloquear
        $this->client->request('POST', $this->operationUrl($workDay), [
            'items' => [$workDay->getId()],
            'submit-justified' => '',
        ]);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(WorkDay::ABSENCE_UNJUSTIFIED, $this->reload($workDay)->getAbsence());
    }

    public function testStudentCannotMarkAbsences(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $this->login($this->getUser('alfa.alumno7'));

        $this->client->request('POST', $this->operationUrl($workDay), [
            'items' => [$workDay->getId()],
            'submit-unjustified' => '',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(WorkDay::ABSENCE_NONE, $this->reload($workDay)->getAbsence());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function operationOutsiderProvider(): iterable
    {
        yield 'alumno de otra estancia' => ['alfa.alumno8'];
        yield 'tutor laboral de otra estancia' => ['alfa.tutorlaboral2'];
        yield 'alumno de FCT' => ['alfa.alumno1'];
    }

    /**
     * @dataProvider operationOutsiderProvider
     */
    public function testOutsidersCannotSeeTheDaysOfAStayThroughTheOperation(string $username): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        $this->login($this->getUser($username));

        // sin indicar ninguna operación concreta, la ruta muestra la pantalla de asistencia con las jornadas
        $this->client->request('POST', $this->operationUrl($workDay), ['items' => [$workDay->getId()]]);

        self::assertResponseStatusCodeSame(403);
    }

    public function testMarkingAnAbsenceThroughTheFormClearsTheActivitiesAndLocksTheDay(): void
    {
        $workDay = $this->workDayOf('alfa.alumno7');
        self::assertGreaterThan(0, count($workDay->getActivities()));
        $this->login($this->getUser('alfa.profesor'));

        $crawler = $this->client->request('GET', $this->formUrl($workDay));
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['work_day_tracking[absence]'] = (string) WorkDay::ABSENCE_JUSTIFIED;
        $this->client->submit($form);

        self::assertResponseRedirects();
        $reloaded = $this->reload($workDay);
        self::assertSame(WorkDay::ABSENCE_JUSTIFIED, $reloaded->getAbsence());
        self::assertCount(0, $reloaded->getActivities());
        self::assertTrue($reloaded->isLocked(), 'Una jornada con falta se bloquea');
    }
}
