<?php

namespace App\Tests\Functional;

use App\Entity\AnsweredSurvey;
use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WltModule\StudentAnsweredSurvey;
use App\Entity\WltModule\WorkTutorAnsweredSurvey;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Avisos de encuestas sin contestar de FP dual que envía el comando periódico: al alumnado (encuesta de alumnado) y
 * al tutor laboral (encuesta de empresa) mientras la encuesta está abierta. Las encuestas se crean en cada test.
 *  - alumno5 (tutor laboral "tutorlaboral") y alumno6 (tutor laboral "tutorlaboral2")
 */
class CronSurveyWarningsTest extends KernelTestCase
{
    use EnablesSurveysTrait;
    use MailerAssertionsTrait;

    /**
     * @return array{int, string}
     */
    private function runCron(): array
    {
        self::bootKernel();
        $tester = new CommandTester((new Application(self::$kernel))->find('app:cron'));
        $status = $tester->execute([]);

        return [$status, $tester->getDisplay()];
    }

    /**
     * @return list<string> direcciones que han recibido un aviso de encuesta
     */
    private function recipients(): array
    {
        $recipients = [];
        foreach (self::getMailerMessages() as $message) {
            self::assertCount(1, $message->getTo(), 'Cada aviso lleva un solo destinatario');
            if (str_contains((string) $message->getSubject(), 'ncuesta')) {
                $recipients[] = $message->getTo()[0]->getAddress();
            }
        }
        sort($recipients);

        return $recipients;
    }

    private function surveyDates(string $title, ?string $start, ?string $end): void
    {
        $em = $this->em();
        $survey = $em->getRepository(Survey::class)->findOneBy(['title' => $title]);
        $survey->setStartTimestamp($start === null ? null : new \DateTime($start))->setEndTimestamp($end === null ? null : new \DateTime($end));
        $em->flush();
        $em->clear();
    }

    private function answerAsStudent(string $username): void
    {
        $em = $this->em();
        $agreement = $this->wltAgreement();
        $survey = $em->getRepository(Survey::class)->findOneBy(['title' => 'Dual alumnado']);
        $answered = (new AnsweredSurvey())->setSurvey($survey)->setTimestamp(new \DateTime());
        $em->persist($answered);
        $em->persist((new StudentAnsweredSurvey())
            ->setProject($em->getReference($agreement->getProject()::class, $agreement->getProject()->getId()))
            ->setStudentEnrollment($em->getReference($agreement->getStudentEnrollment()::class, $agreement->getStudentEnrollment()->getId()))
            ->setAnsweredSurvey($answered));
        $em->flush();
        $em->clear();
    }

    private function answerAsWorkTutor(): void
    {
        $em = $this->em();
        $agreement = $this->wltAgreement();
        $survey = $em->getRepository(Survey::class)->findOneBy(['title' => 'Dual empresa']);
        $answered = (new AnsweredSurvey())->setSurvey($survey)->setTimestamp(new \DateTime());
        $em->persist($answered);
        $em->persist((new WorkTutorAnsweredSurvey())
            ->setProject($em->getReference($agreement->getProject()::class, $agreement->getProject()->getId()))
            ->setAcademicYear($em->getReference($agreement->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear()::class, $agreement->getStudentEnrollment()->getGroup()->getGrade()->getTraining()->getAcademicYear()->getId()))
            ->setWorkTutor($em->getReference(Person::class, $agreement->getWorkTutor()->getId()))
            ->setAnsweredSurvey($answered));
        $em->flush();
        $em->clear();
    }

    public function testNoWarningsAreSentWhenThereAreNoSurveys(): void
    {
        [$status] = $this->runCron();

        self::assertSame(0, $status);
        self::assertSame([], $this->recipients());
    }

    public function testStudentsAndWorkTutorsWithUnansweredSurveysAreWarned(): void
    {
        $this->enableSurveys();

        [$status, $display] = $this->runCron();

        self::assertSame(0, $status, $display);
        $recipients = $this->recipients();
        foreach (['alfa.alumno5', 'alfa.alumno6', 'alfa.tutorlaboral', 'alfa.tutorlaboral2'] as $username) {
            self::assertContains("$username@example.com", $recipients);
        }
    }

    public function testStudentWhoAnsweredIsNoLongerWarned(): void
    {
        $this->enableSurveys();
        $this->answerAsStudent('alfa.alumno5');

        $this->runCron();

        $recipients = $this->recipients();
        self::assertNotContains('alfa.alumno5@example.com', $recipients);
        self::assertContains('alfa.alumno6@example.com', $recipients);
    }

    public function testWorkTutorWhoAnsweredIsNoLongerWarned(): void
    {
        $this->enableSurveys();
        $this->answerAsWorkTutor();

        $this->runCron();

        $recipients = $this->recipients();
        self::assertNotContains('alfa.tutorlaboral@example.com', $recipients);
        self::assertContains('alfa.tutorlaboral2@example.com', $recipients);
    }

    public function testTeachersWithAnUnansweredEducationalTutorSurveyAreWarned(): void
    {
        $this->enableSurveys();
        $em = $this->em();
        $project = $this->wltAgreement()->getProject();
        $em->find($project::class, $project->getId())->setEducationalTutorSurvey($this->createSurvey($em, 'Dual centro'));
        $em->flush();
        $em->clear();

        [$status, $display] = $this->runCron();

        self::assertSame(0, $status, $display);
        // el aviso indica los días que quedan para que se cierre la encuesta
        $teacherMessages = array_filter(
            self::getMailerMessages(),
            static fn($message): bool => !preg_match('/^alfa\.(alumno|tutorlaboral)/', $message->getTo()[0]->getAddress())
        );
        self::assertNotEmpty($teacherMessages, 'El profesorado con encuesta de centro sin contestar recibe un aviso');
        foreach ($teacherMessages as $message) {
            self::assertMatchesRegularExpression('/Queda\(n\) \d+ día\(s\)/', (string) $message->getTextBody());
        }
    }

    public function testClosedAndNotYetOpenSurveysAreNotWarned(): void
    {
        $this->enableSurveys();
        $this->surveyDates('Dual alumnado', '-2 months', '-1 day');
        $this->surveyDates('Dual empresa', '+1 day', '+2 months');

        [$status] = $this->runCron();

        self::assertSame(0, $status);
        $recipients = $this->recipients();
        foreach (['alfa.alumno5', 'alfa.alumno6', 'alfa.tutorlaboral', 'alfa.tutorlaboral2'] as $username) {
            self::assertNotContains("$username@example.com", $recipients);
        }
    }

    public function testStudentWithoutEmailIsSkippedWithoutFailing(): void
    {
        $this->enableSurveys();
        $em = $this->em();
        $em->getRepository(Person::class)->findOneBy(['loginUsername' => 'alfa.alumno5'])->setEmailAddress('');
        $em->flush();
        $em->clear();

        [$status] = $this->runCron();

        self::assertSame(0, $status);
        self::assertNotContains('@example.com', $this->recipients());
        self::assertContains('alfa.alumno6@example.com', $this->recipients());
    }
}
