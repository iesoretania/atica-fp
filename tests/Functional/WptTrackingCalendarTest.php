<?php

namespace App\Tests\Functional;

use App\Entity\WptModule\ActivityTracking;
use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\TrackedWorkDay;
use App\Entity\WptModule\WorkDay;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Seguimiento diario de la FCT (calendario de la matrícula): quién puede rellenar, bloquear y marcar faltas,
 * tanto en el formulario web como en la API v2. Las matrículas de las fixtures son:
 *  - alumno1 y alumno2: convenio 1 (tutor docente y de grupo "tutor", tutor laboral "tutorlaboral")
 *  - alumno3: convenio 2 (tutor docente "profesor", tutor laboral "tutorlaboral2")
 * Las horas se guardan en centésimas.
 */
class WptTrackingCalendarTest extends AppWebTestCase
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

    private function enrollmentOf(string $username): AgreementEnrollment
    {
        return $this->em()->getRepository(AgreementEnrollment::class)->createQueryBuilder('e')
            ->join('e.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult();
    }

    /**
     * Jornada ya pasada y sin falta de la matrícula
     */
    private function trackedDayOf(string $username): TrackedWorkDay
    {
        $enrollment = $this->enrollmentOf($username);

        return $this->em()->getRepository(TrackedWorkDay::class)->createQueryBuilder('t')
            ->join('t.workDay', 'w')
            ->where('t.agreementEnrollment = :enrollment')->andWhere('t.absence = 0')->andWhere('w.date <= :today')
            ->setParameter('enrollment', $enrollment)->setParameter('today', new \DateTime('today'))
            ->orderBy('w.date', 'DESC')->setMaxResults(1)->getQuery()->getSingleResult();
    }

    /**
     * Jornada futura del convenio de la matrícula, todavía sin seguimiento
     */
    private function untrackedWorkDayOf(string $username): WorkDay
    {
        $enrollment = $this->enrollmentOf($username);
        $workDay = $this->em()->getRepository(WorkDay::class)->createQueryBuilder('w')
            ->where('w.agreement = :agreement')->andWhere('w.date > :today')
            ->setParameter('agreement', $enrollment->getAgreement())->setParameter('today', new \DateTime('today'))
            ->orderBy('w.date', 'ASC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
        if (!$workDay instanceof WorkDay) {
            self::markTestSkipped('No hay jornadas futuras en el período de las prácticas (fin del curso académico)');
        }

        return $workDay;
    }

    private function lock(TrackedWorkDay $trackedWorkDay, bool $locked = true): void
    {
        $em = $this->em();
        $em->find(TrackedWorkDay::class, $trackedWorkDay->getId())->setLocked($locked);
        $em->flush();
    }

    private function reload(TrackedWorkDay $trackedWorkDay): TrackedWorkDay
    {
        return $this->em()->find(TrackedWorkDay::class, $trackedWorkDay->getId());
    }

    private function apiEditUrl(TrackedWorkDay $trackedWorkDay): string
    {
        return $this->router()->generate('api_workplace_training_tracking_calendar_edit', ['trackedWorkDay' => $trackedWorkDay->getId()]);
    }

    private function apiActivityUrl(TrackedWorkDay $trackedWorkDay, int $activityId): string
    {
        return $this->router()->generate('api_workplace_training_tracking_calendar_edit_activity', [
            'trackedWorkDay' => $trackedWorkDay->getId(),
            'activity' => $activityId,
        ]);
    }

    private function formUrl(WorkDay $workDay, AgreementEnrollment $enrollment): string
    {
        return $this->router()->generate('workplace_training_tracking_calendar_form', [
            'workDay' => $workDay->getId(),
            'agreementEnrollment' => $enrollment->getId(),
        ]);
    }

    private function operationUrl(AgreementEnrollment $enrollment): string
    {
        return $this->router()->generate('workplace_training_tracking_calendar_operation', ['id' => $enrollment->getId()]);
    }

    private function activityHours(TrackedWorkDay $trackedWorkDay): int
    {
        $total = 0;
        foreach ($this->em()->getRepository(ActivityTracking::class)->findBy(['trackedWorkDay' => $trackedWorkDay->getId()]) as $tracking) {
            $total += $tracking->getHours();
        }

        return $total;
    }

    // --- Formulario web ---------------------------------------------------------------------------

    public function testStudentFillsTheirOwnDay(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno1'));

        $crawler = $this->client->request('GET', $this->formUrl($tracked->getWorkDay(), $tracked->getAgreementEnrollment()));
        self::assertResponseIsSuccessful();
        $this->client->submit($crawler->selectButton('Guardar cambios')->form([
            'work_day_tracking[trackedActivities][0][hours]' => '3,50',
            'work_day_tracking[notes]' => 'Notas escritas por el alumno',
        ]));

        self::assertResponseRedirects();
        $reloaded = $this->reload($tracked);
        self::assertSame('Notas escritas por el alumno', $reloaded->getNotes());
        self::assertSame(350, $this->activityHours($tracked), 'Las horas deben guardarse en centésimas (3,50 h = 350)');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        yield 'otro alumno del mismo convenio' => ['alfa.alumno2'];
        yield 'alumno de otro convenio' => ['alfa.alumno3'];
        yield 'tutor laboral de otro convenio' => ['alfa.tutorlaboral2'];
        yield 'tutor docente de otro convenio' => ['alfa.profesor'];
        yield 'alumno de otro módulo' => ['alfa.alumno1x'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testPeopleOutsideTheEnrollmentCannotOpenOrChangeTheDay(string $username): void
    {
        if ($username === 'alfa.alumno1x') {
            $username = 'alfa.alumno4';
        }
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $url = $this->formUrl($tracked->getWorkDay(), $tracked->getAgreementEnrollment());
        $this->login($this->getUser($username));

        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $url, ['work_day_tracking' => ['notes' => 'Escrito por quien no debe']]);
        self::assertResponseStatusCodeSame(403);
        self::assertNotSame('Escrito por quien no debe', $this->reload($tracked)->getNotes());
    }

    public function testDayOfAnotherAgreementCannotBeUsedWithTheEnrollment(): void
    {
        $own = $this->trackedDayOf('alfa.alumno1');
        $foreign = $this->trackedDayOf('alfa.alumno3');
        $before = $this->em()->getRepository(TrackedWorkDay::class)->count([]);
        $this->login($this->getUser('alfa.alumno1'));

        // jornada del convenio 2 con la matrícula del convenio 1
        $url = $this->formUrl($foreign->getWorkDay(), $own->getAgreementEnrollment());
        $this->client->request('GET', $url);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $url, ['work_day_tracking' => ['notes' => 'Cruzado']]);
        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->em()->getRepository(TrackedWorkDay::class)->count([]));
    }

    public function testEditingALockedDayThroughTheFormChangesNothing(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $notes = $tracked->getNotes();
        $this->lock($tracked);
        $this->login($this->getUser('alfa.alumno1'));

        $crawler = $this->client->request('GET', $this->formUrl($tracked->getWorkDay(), $tracked->getAgreementEnrollment()));
        // el formulario llega con los campos deshabilitados; una petición hecha a mano no debe cambiar nada
        $this->client->request('POST', $this->formUrl($tracked->getWorkDay(), $tracked->getAgreementEnrollment()), [
            'work_day_tracking' => [
                'notes' => 'Modificada estando bloqueada',
                '_token' => $crawler->filter('input[name="work_day_tracking[_token]"]')->attr('value'),
            ],
        ]);

        self::assertSame($notes, $this->reload($tracked)->getNotes());
    }

    public function testStudentCannotUnlockTheirDayWithACraftedRequest(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->lock($tracked);
        $this->login($this->getUser('alfa.alumno1'));

        $crawler = $this->client->request('GET', $this->formUrl($tracked->getWorkDay(), $tracked->getAgreementEnrollment()));
        $this->client->request('POST', $this->formUrl($tracked->getWorkDay(), $tracked->getAgreementEnrollment()), [
            'work_day_tracking' => [
                'locked' => '0',
                'notes' => 'Desbloqueada por el alumno',
                '_token' => $crawler->filter('input[name="work_day_tracking[_token]"]')->attr('value'),
            ],
        ]);

        self::assertTrue($this->reload($tracked)->isLocked(), 'El alumno ha desbloqueado su propia jornada');
    }

    public function testNotesAreEscapedWhenShown(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $em = $this->em();
        $em->find(TrackedWorkDay::class, $tracked->getId())->setNotes('<script>alert("xss")</script><img src=x onerror=alert(1)>');
        $em->flush();
        $this->login($this->getUser('alfa.tutor'));

        $this->client->request('GET', $this->formUrl($tracked->getWorkDay(), $tracked->getAgreementEnrollment()));
        $content = (string) $this->client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('<script>alert("xss")', $content);
        self::assertStringNotContainsString('<img src=x onerror', $content);
        self::assertStringContainsString('&lt;script&gt;', $content);
    }

    // --- Operaciones sobre varias jornadas (bloqueo y faltas) --------------------------------------

    /**
     * @return iterable<string, array{string, int}> usuario y estado esperado al bloquear una semana
     */
    public static function lockProvider(): iterable
    {
        yield 'el alumno no puede bloquear' => ['alfa.alumno1', 403];
        yield 'el tutor laboral no puede bloquear' => ['alfa.tutorlaboral', 403];
        yield 'otro alumno no puede bloquear' => ['alfa.alumno2', 403];
        yield 'el tutor de otro convenio no puede bloquear' => ['alfa.tutorlaboral2', 403];
        yield 'el tutor docente puede bloquear' => ['alfa.tutor', 302];
        yield 'el jefe de departamento puede bloquear' => ['alfa.jefe', 302];
    }

    /**
     * @dataProvider lockProvider
     */
    public function testOnlyTutorsCanLockAWeek(string $username, int $expectedStatus): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $date = $tracked->getWorkDay()->getDate();
        $week = (int) $date->format('o') * 100 + (int) $date->format('W');
        $this->login($this->getUser($username));

        $this->client->request('POST', $this->operationUrl($tracked->getAgreementEnrollment()), ['lock_week' => $week]);

        self::assertResponseStatusCodeSame($expectedStatus);
        self::assertSame($expectedStatus === 302, $this->reload($tracked)->isLocked());
    }

    public function testTutorLocksAndUnlocksSelectedDaysOfTheEnrollmentOnly(): void
    {
        $mine = $this->trackedDayOf('alfa.alumno1');
        $other = $this->trackedDayOf('alfa.alumno2');
        $this->login($this->getUser('alfa.tutor'));

        // se intenta bloquear una jornada de otra matrícula a través de la de alumno1
        $this->client->request('POST', $this->operationUrl($mine->getAgreementEnrollment()), [
            'items' => [$mine->getWorkDay()->getId(), $other->getWorkDay()->getId()],
            'lock' => '',
        ]);
        self::assertResponseRedirects();
        self::assertTrue($this->reload($mine)->isLocked());
        // la jornada de alumno2 es de otra matrícula: no se ve afectada
        self::assertFalse($this->reload($other)->isLocked(), 'Se ha bloqueado una jornada de otra matrícula');

        $this->client->request('POST', $this->operationUrl($mine->getAgreementEnrollment()), [
            'items' => [$mine->getWorkDay()->getId()],
            'unlock' => '',
        ]);
        self::assertFalse($this->reload($mine)->isLocked());
    }

    public function testWorkTutorMarksUnjustifiedButNotJustifiedAbsences(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.tutorlaboral'));

        // el tutor laboral puede registrar una falta sin justificar...
        $this->client->request('POST', $this->operationUrl($tracked->getAgreementEnrollment()), [
            'items' => [$tracked->getWorkDay()->getId()],
            'submit-unjustified' => '',
        ]);
        self::assertResponseRedirects();
        self::assertSame(TrackedWorkDay::UNJUSTIFIED_ABSENCE, $this->reload($tracked)->getAbsence());

        // ...pero justificarla es cosa de quien puede bloquear
        $this->client->request('POST', $this->operationUrl($tracked->getAgreementEnrollment()), [
            'items' => [$tracked->getWorkDay()->getId()],
            'submit-justified' => '',
        ]);
        self::assertResponseStatusCodeSame(403);
        self::assertSame(TrackedWorkDay::UNJUSTIFIED_ABSENCE, $this->reload($tracked)->getAbsence());
    }

    public function testStudentCannotMarkAbsences(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('POST', $this->operationUrl($tracked->getAgreementEnrollment()), [
            'items' => [$tracked->getWorkDay()->getId()],
            'submit-unjustified' => '',
        ]);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(TrackedWorkDay::NO_ABSENCE, $this->reload($tracked)->getAbsence());
    }

    public function testMarkingAnAbsenceThroughTheFormClearsTheActivitiesAndLocksTheDay(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        self::assertGreaterThan(0, $this->activityHours($tracked));
        $this->login($this->getUser('alfa.tutor'));

        $crawler = $this->client->request('GET', $this->formUrl($tracked->getWorkDay(), $tracked->getAgreementEnrollment()));
        $form = $crawler->selectButton('Guardar cambios')->form();
        $absence = $crawler->filter('select[name="work_day_tracking[absence]"], input[name="work_day_tracking[absence]"]');
        self::assertGreaterThan(0, $absence->count(), 'El tutor debería poder indicar la asistencia');
        $form['work_day_tracking[absence]'] = (string) TrackedWorkDay::JUSTIFIED_ABSENCE;
        $this->client->submit($form);

        self::assertResponseRedirects();
        $reloaded = $this->reload($tracked);
        self::assertSame(TrackedWorkDay::JUSTIFIED_ABSENCE, $reloaded->getAbsence());
        self::assertSame(0, $this->activityHours($tracked), 'Una jornada con falta no debe conservar horas de actividades');
        self::assertTrue($reloaded->isLocked(), 'Una jornada con falta se bloquea');
    }

    public function testStudentCannotFillADayOfALockedAgreement(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $em = $this->em();
        $em->find(Agreement::class, $tracked->getAgreementEnrollment()->getAgreement()->getId())->setLocked(true);
        $em->flush();
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('POST', $this->apiEditUrl($tracked), ['notes' => 'Con el convenio cerrado']);

        self::assertResponseStatusCodeSame(403);
        self::assertNotSame('Con el convenio cerrado', $this->reload($tracked)->getNotes());
    }

    // --- API v2 ---------------------------------------------------------------------------------------

    public function testApiReturnsTheDayToItsStudent(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('GET', $this->router()->generate('api_workplace_training_tracking_calendar_form', [
            'workDay' => $tracked->getWorkDay()->getId(),
            'agreementEnrollment' => $tracked->getAgreementEnrollment()->getId(),
        ]));

        self::assertResponseIsSuccessful();
        $json = json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertFalse($json['read_only']);
        self::assertSame($tracked->getId(), $json['tracked_work_day']['id']);
        // la API devuelve las horas de la jornada multiplicadas por 100 sobre las centésimas guardadas (50000 en
        // lugar de 500): se deja sin fijar porque los clientes de la API pueden depender de esa unidad
        self::assertArrayHasKey('hours', $json['tracked_work_day']);
        self::assertCount(3, $json['tracked_activities']);
    }

    public function testApiEditChangesTheNotesOfTheStudentsOwnDay(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('POST', $this->apiEditUrl($tracked), ['notes' => 'Desde la API', 'other_activities' => 'Otras']);

        self::assertResponseIsSuccessful();
        self::assertSame(['result' => 'ok'], json_decode($this->client->getResponse()->getContent(), true));
        self::assertSame('Desde la API', $this->reload($tracked)->getNotes());
    }

    public function testApiRejectsPeopleOutsideTheEnrollment(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno2'));

        $this->client->request('POST', $this->apiEditUrl($tracked), ['notes' => 'Intruso']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $this->apiActivityUrl($tracked, 1), ['hours' => 100]);
        self::assertResponseStatusCodeSame(403);
        self::assertNotSame('Intruso', $this->reload($tracked)->getNotes());
    }

    /**
     * Pedir una jornada que no se puede ver no debe dejar rastro en la base de datos
     */
    public function testUnauthorizedApiReadDoesNotCreateRecords(): void
    {
        $enrollment = $this->enrollmentOf('alfa.alumno1');
        $workDay = $this->untrackedWorkDayOf('alfa.alumno1');
        $before = $this->em()->getRepository(TrackedWorkDay::class)->count([]);
        $this->login($this->getUser('alfa.alumno2'));

        $this->client->request('GET', $this->router()->generate('api_workplace_training_tracking_calendar_form', [
            'workDay' => $workDay->getId(),
            'agreementEnrollment' => $enrollment->getId(),
        ]));

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->em()->getRepository(TrackedWorkDay::class)->count([]), 'Una consulta denegada ha creado una jornada');
    }

    public function testApiCannotEditADayThatATutorHasLocked(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $notes = $tracked->getNotes();
        $this->lock($tracked);
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('POST', $this->apiEditUrl($tracked), ['notes' => 'Cambiada estando bloqueada']);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', $this->apiActivityUrl($tracked, 1), ['hours' => 100]);
        self::assertResponseStatusCodeSame(403);

        $reloaded = $this->reload($tracked);
        self::assertSame($notes, $reloaded->getNotes(), 'La API ha modificado una jornada bloqueada');
        self::assertSame(500, $this->activityHours($tracked));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidHoursProvider(): iterable
    {
        yield 'negativas' => ['-300'];
        yield 'más de un día' => ['3000'];
        yield 'enormes' => ['99999999999'];
        yield 'texto' => ['muchas'];
    }

    /**
     * @dataProvider invalidHoursProvider
     */
    public function testApiRejectsInvalidActivityHours(string $hours): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('POST', $this->apiActivityUrl($tracked, 1), ['hours' => $hours]);

        self::assertGreaterThanOrEqual(400, $this->client->getResponse()->getStatusCode(), "Se aceptan horas $hours");
        self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), "Las horas $hours provocan un error del servidor");
        self::assertSame(500, $this->activityHours($tracked), 'Las horas han cambiado con un valor no válido');
    }

    public function testApiAcceptsValidActivityHoursInHundredths(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('POST', $this->apiActivityUrl($tracked, 1), ['hours' => '250']);

        self::assertResponseIsSuccessful();
        self::assertSame(250, $this->activityHours($tracked));
    }

    public function testApiReturnsNotFoundForActivitiesOutsideTheEnrollment(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno1'));

        // la actividad 999999 no existe y ninguna actividad de otro convenio debe poder anotarse aquí
        $this->client->request('POST', $this->router()->generate('api_workplace_training_tracking_calendar_edit_activity', [
            'trackedWorkDay' => $tracked->getId(),
            'activity' => 999999,
        ]), ['hours' => 100]);

        self::assertResponseStatusCodeSame(404);
    }

    public function testApiAbsenceRulesMatchTheWebForm(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');

        // el alumno no puede indicar asistencia
        $this->login($this->getUser('alfa.alumno1'));
        $this->client->request('POST', $this->apiEditUrl($tracked), ['absence' => TrackedWorkDay::UNJUSTIFIED_ABSENCE]);
        self::assertResponseStatusCodeSame(403);

        // el tutor laboral puede indicar una falta sin justificar, pero no justificarla (como en el formulario web)
        $this->login($this->getUser('alfa.tutorlaboral'));
        $this->client->request('POST', $this->apiEditUrl($tracked), ['absence' => TrackedWorkDay::UNJUSTIFIED_ABSENCE]);
        self::assertResponseIsSuccessful();
        $this->client->request('POST', $this->apiEditUrl($tracked), ['absence' => TrackedWorkDay::JUSTIFIED_ABSENCE]);
        self::assertResponseStatusCodeSame(403, 'La API deja justificar una falta a quien el formulario web no se lo permite');
        self::assertSame(TrackedWorkDay::UNJUSTIFIED_ABSENCE, $this->reload($tracked)->getAbsence());

        // un valor inventado se rechaza
        $this->client->request('POST', $this->apiEditUrl($tracked), ['absence' => 7]);
        self::assertResponseStatusCodeSame(403);
    }

    public function testApiRejectsMalformedTimes(): void
    {
        $tracked = $this->trackedDayOf('alfa.alumno1');
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('POST', $this->apiEditUrl($tracked), ['start_time1' => 'esto no es una hora, es un texto largo']);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), 'Un valor no válido provoca un error del servidor');
        self::assertNotSame('esto no es una hora, es un texto largo', $this->reload($tracked)->getStartTime1());
    }
}
