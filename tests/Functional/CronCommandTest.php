<?php

namespace App\Tests\Functional;

use App\Entity\Person;
use App\Entity\WltModule\WorkDay;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Avisos periódicos de la FP dual (inactividad del alumnado y encuestas pendientes). El transporte de
 * correo de los tests no envía nada: se comprueban los mensajes generados.
 */
class CronCommandTest extends KernelTestCase
{
    use MailerAssertionsTrait;

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    /**
     * @return array{int, string} código de salida y salida del comando
     */
    private function runCron(): array
    {
        self::bootKernel();
        $tester = new CommandTester((new Application(self::$kernel))->find('app:cron'));
        $status = $tester->execute([]);

        return [$status, $tester->getDisplay()];
    }

    /**
     * Deja sin actividades las jornadas de más de una semana del alumno, como si no las hubiera rellenado
     */
    private function leaveUnfilled(string $username): void
    {
        $em = $this->em();
        $workDays = $em->getRepository(WorkDay::class)->createQueryBuilder('w')
            ->join('w.agreement', 'a')->join('a.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->andWhere('w.date < :limit')->andWhere('w.absence = 0')
            ->setParameter('username', $username)->setParameter('limit', new \DateTime('-8 days'))
            ->getQuery()->getResult();
        self::assertNotEmpty($workDays, 'Las fixtures deberían tener jornadas de más de una semana');
        foreach ($workDays as $workDay) {
            $workDay->setActivityRealizations(new ArrayCollection());
        }
        $em->flush();
    }

    private function setEmail(string $username, string $email): void
    {
        $em = $this->em();
        $em->getRepository(Person::class)->findOneBy(['loginUsername' => $username])->setEmailAddress($email);
        $em->flush();
    }

    public function testCronRunsWithoutErrors(): void
    {
        [$status, $display] = $this->runCron();

        self::assertSame(0, $status);
        self::assertStringNotContainsString('Exception', $display);
        // la tabla de seguimiento incluye los acuerdos del curso actual de cada organización
        self::assertStringContainsString('I.E.S. Alfa', $display);
        self::assertStringContainsString('I.E.S. Beta', $display);
    }

    public function testNoWarningsAreSentWhenEveryDayIsFilled(): void
    {
        $this->runCron();

        self::assertCount(0, self::getMailerMessages());
    }

    public function testStudentWithUnfilledDaysReceivesAWarningAtTheirOwnAddress(): void
    {
        $this->leaveUnfilled('alfa.alumno5');

        $this->runCron();

        $messages = self::getMailerMessages();
        self::assertCount(1, $messages);
        self::assertCount(1, $messages[0]->getTo());
        self::assertSame('alfa.alumno5@example.com', $messages[0]->getTo()[0]->getAddress());
        self::assertNotSame('', $messages[0]->getSubject());
        self::assertNotSame('', $messages[0]->getTextBody());
        // el aviso lista los días pendientes
        self::assertStringContainsString('- ', $messages[0]->getTextBody());
    }

    public function testStudentsWithoutEmailAreSkippedWithoutFailing(): void
    {
        $this->leaveUnfilled('alfa.alumno5');
        $this->setEmail('alfa.alumno5', '');

        [$status, $display] = $this->runCron();

        self::assertSame(0, $status);
        self::assertCount(0, self::getMailerMessages());
        self::assertStringContainsString('Alumno 5', $display);
    }

    public function testRunningTwiceSendsTheSameWarnings(): void
    {
        $this->leaveUnfilled('alfa.alumno5');
        $this->runCron();
        $first = count(self::getMailerMessages());
        $this->runCron();

        // el comando no tiene estado: cada ejecución genera los mismos avisos
        self::assertSame($first, count(self::getMailerMessages()));
        self::assertSame(1, $first);
    }
}
