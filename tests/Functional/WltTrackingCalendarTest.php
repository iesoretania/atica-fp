<?php

namespace App\Tests\Functional;

use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\WorkDay;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Seguimiento diario de la FP dual (calendario del acuerdo): quién puede rellenar, bloquear y marcar faltas.
 * Los acuerdos de las fixtures son:
 *  - alumno5: acuerdo 1 (tutor docente y de grupo "tutor", tutor laboral "tutorlaboral")
 *  - alumno6: acuerdo 2 (tutor docente "profesor", tutor laboral "tutorlaboral2")
 * Las horas de jornada de FP dual son horas enteras.
 */
class WltTrackingCalendarTest extends AppWebTestCase
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

    private function agreementOf(string $username): Agreement
    {
        return $this->em()->getRepository(Agreement::class)->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult();
    }

    /**
     * Jornada ya pasada y sin falta del acuerdo
     */
    private function workDayOf(string $username): WorkDay
    {
        return $this->em()->getRepository(WorkDay::class)->createQueryBuilder('w')
            ->where('w.agreement = :agreement')->andWhere('w.absence = 0')->andWhere('w.date <= :today')
            ->setParameter('agreement', $this->agreementOf($username))->setParameter('today', new \DateTime('today'))
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
        return $this->router()->generate('work_linked_training_tracking_calendar_form', ['id' => $workDay->getId()]);
    }

    private function operationUrl(WorkDay $workDay): string
    {
        return $this->router()->generate('work_linked_training_tracking_calendar_operation', [
            'id' => $workDay->getAgreement()->getId(),
        ]);
    }

    private function token(WorkDay $workDay): string
    {
        $crawler = $this->client->request('GET', $this->formUrl($workDay));

        return $crawler->filter('input[name="work_day_tracking[_token]"]')->attr('value');
    }

    public function testStudentFillsTheirOwnDay(): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
        $this->login($this->getUser('alfa.alumno5'));

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
        yield 'alumno de otro acuerdo' => ['alfa.alumno6'];
        yield 'tutor laboral de otro acuerdo' => ['alfa.tutorlaboral2'];
        yield 'tutor docente de otro acuerdo' => ['alfa.profesor'];
        yield 'alumno de FCT' => ['alfa.alumno1'];
        yield 'alumno de otro centro' => ['beta.alumno5'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testPeopleOutsideTheAgreementCannotOpenOrChangeTheDay(string $username): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
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
        $workDay = $this->workDayOf('alfa.alumno5');
        $notes = $workDay->getNotes();
        $this->lock($workDay);
        $this->login($this->getUser('alfa.alumno5'));

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
        $workDay = $this->workDayOf('alfa.alumno5');
        $this->lock($workDay);
        $this->login($this->getUser('alfa.alumno5'));

        $token = $this->token($workDay);
        $this->client->request('POST', $this->formUrl($workDay), [
            'work_day_tracking' => ['locked' => '0', 'notes' => 'Desbloqueada por el alumno', '_token' => $token],
        ]);

        self::assertTrue($this->reload($workDay)->isLocked(), 'El alumno ha desbloqueado su propia jornada');
    }

    public function testStudentCannotMarkAnAbsenceThroughTheForm(): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
        $this->login($this->getUser('alfa.alumno5'));

        $token = $this->token($workDay);
        $this->client->request('POST', $this->formUrl($workDay), [
            'work_day_tracking' => ['absence' => (string) WorkDay::JUSTIFIED_ABSENCE, '_token' => $token],
        ]);

        self::assertSame(WorkDay::NO_ABSENCE, $this->reload($workDay)->getAbsence());
    }

    public function testNotesAreEscapedWhenShown(): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
        $em = $this->em();
        $em->find(WorkDay::class, $workDay->getId())->setNotes('<script>alert("xss")</script><img src=x onerror=alert(1)>');
        $em->flush();
        $this->login($this->getUser('alfa.tutor'));

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
        $workDay = $this->workDayOf('alfa.alumno5');
        $this->login($this->getUser('alfa.alumno5'));

        $token = $this->token($workDay);
        $this->client->request('POST', $this->formUrl($workDay), [
            'work_day_tracking' => ['startTime1' => $time, 'endTime1' => '14:00', '_token' => $token],
        ]);

        self::assertResponseIsSuccessful(); // se vuelve a mostrar el formulario con el error
        self::assertNotSame($time, $this->reload($workDay)->getStartTime1());
    }

    /**
     * @return iterable<string, array{string, int}> usuario y estado esperado al bloquear una semana
     */
    public static function lockProvider(): iterable
    {
        yield 'el alumno no puede bloquear' => ['alfa.alumno5', 403];
        yield 'el tutor laboral no puede bloquear' => ['alfa.tutorlaboral', 403];
        yield 'el tutor de otro acuerdo no puede bloquear' => ['alfa.tutorlaboral2', 403];
        yield 'el tutor docente puede bloquear' => ['alfa.tutor', 302];
        yield 'el coordinador puede bloquear' => ['alfa.coordinador', 302];
    }

    /**
     * @dataProvider lockProvider
     */
    public function testOnlyTutorsCanLockAWeek(string $username, int $expectedStatus): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
        $date = $workDay->getDate();
        $week = (int) $date->format('o') * 100 + (int) $date->format('W');
        $this->login($this->getUser($username));

        $this->client->request('POST', $this->operationUrl($workDay), ['lock_week' => $week]);

        self::assertResponseStatusCodeSame($expectedStatus);
        self::assertSame($expectedStatus === 302, $this->reload($workDay)->isLocked());
    }

    public function testTutorLocksAndUnlocksSelectedDaysOfTheAgreementOnly(): void
    {
        $mine = $this->workDayOf('alfa.alumno5');
        $other = $this->workDayOf('alfa.alumno6');
        $this->login($this->getUser('alfa.tutor'));

        // se intenta bloquear una jornada de otro acuerdo a través del primero
        $this->client->request('POST', $this->operationUrl($mine), [
            'items' => [$mine->getId(), $other->getId()],
            'lock' => '',
        ]);
        self::assertResponseRedirects();
        self::assertTrue($this->reload($mine)->isLocked());
        self::assertFalse($this->reload($other)->isLocked(), 'Se ha bloqueado una jornada de otro acuerdo');

        $this->client->request('POST', $this->operationUrl($mine), ['items' => [$mine->getId()], 'unlock' => '']);
        self::assertFalse($this->reload($mine)->isLocked());
    }

    public function testWorkTutorCanMarkUnjustifiedAbsencesWithoutBeingAbleToLock(): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
        $this->login($this->getUser('alfa.tutorlaboral'));

        $this->client->request('POST', $this->operationUrl($workDay), [
            'items' => [$workDay->getId()],
            'confirm' => 'ok',
        ]);

        self::assertResponseRedirects();
        self::assertSame(WorkDay::UNJUSTIFIED_ABSENCE, $this->reload($workDay)->getAbsence());
    }

    public function testStudentCannotMarkAbsences(): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
        $this->login($this->getUser('alfa.alumno5'));

        $this->client->request('POST', $this->operationUrl($workDay), [
            'items' => [$workDay->getId()],
            'confirm' => 'ok',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(WorkDay::NO_ABSENCE, $this->reload($workDay)->getAbsence());
    }

    public function testMarkingAnAbsenceThroughTheFormClearsTheActivitiesAndLocksTheDay(): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
        self::assertGreaterThan(0, count($workDay->getActivityRealizations()));
        $this->login($this->getUser('alfa.tutor'));

        $crawler = $this->client->request('GET', $this->formUrl($workDay));
        $form = $crawler->selectButton('Guardar cambios')->form();
        $form['work_day_tracking[absence]'] = (string) WorkDay::JUSTIFIED_ABSENCE;
        $this->client->submit($form);

        self::assertResponseRedirects();
        $reloaded = $this->reload($workDay);
        self::assertSame(WorkDay::JUSTIFIED_ABSENCE, $reloaded->getAbsence());
        self::assertCount(0, $reloaded->getActivityRealizations());
        self::assertTrue($reloaded->isLocked(), 'Una jornada con falta se bloquea');
    }

    public function testStudentCannotFillADayOfALockedAgreement(): void
    {
        $workDay = $this->workDayOf('alfa.alumno5');
        $em = $this->em();
        // en FP dual el cierre es del proyecto al que pertenece el acuerdo
        $em->find(Agreement::class, $workDay->getAgreement()->getId())->getProject()->setLocked(true);
        $em->flush();
        $this->login($this->getUser('alfa.alumno5'));

        $token = $this->token($workDay);
        $this->client->request('POST', $this->formUrl($workDay), [
            'work_day_tracking' => ['notes' => 'Con el acuerdo cerrado', '_token' => $token],
        ]);

        self::assertNotSame('Con el acuerdo cerrado', $this->reload($workDay)->getNotes());
    }
}
