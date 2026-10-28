<?php

namespace App\Tests\Functional;

use App\Entity\AnsweredSurvey;
use App\Entity\AnsweredSurveyQuestion;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Organization;
use App\Entity\Survey;
use App\Entity\SurveyQuestion;
use App\Entity\WltModule\Agreement;
use App\Entity\WptModule\AgreementEnrollment;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Formularios de las encuestas de alumnado y de empresa en FCT, FP dual y FFE. Las fixtures no incluyen
 * encuestas, por lo que cada test crea las suyas. Participantes:
 *  - FCT: alumno1 y alumno2 (acuerdo 1, tutor laboral "tutorlaboral", tutor docente "profesor")
 *  - FP dual: alumno5 (tutor de grupo "tutor", tutor laboral "tutorlaboral")
 *  - FFE: alumno7 (tutor docente "profesor", tutor laboral "tutorlaboral")
 */
class SurveyFormsTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function createSurvey(EntityManagerInterface $em, string $title): Survey
    {
        $survey = (new Survey())
            ->setOrganization($em->getRepository(Organization::class)->findOneBy(['code' => DemoData::ORG_A_CODE]))
            ->setTitle($title)
            ->setStartTimestamp(new \DateTime('-1 year'))
            ->setEndTimestamp(new \DateTime('+1 year'));
        $em->persist($survey);
        $question = (new SurveyQuestion())
            ->setSurvey($survey)->setDescription('Valoración general')->setType(SurveyQuestion::RANGE_1_5)
            ->setMandatory(false)->setOrderNr(1);
        $em->persist($question);

        return $survey;
    }

    /**
     * Asigna encuestas de alumnado y de empresa a la fase de FCT, al proyecto de FP dual y al programa de FFE
     */
    private function enableSurveys(): void
    {
        // los identificadores se obtienen antes de modificar nada: los helpers vacían el gestor de entidades
        $enrollmentId = $this->wptEnrollment('alfa.alumno1')->getId();
        $agreementId = $this->wltAgreement()->getId();
        $stayId = $this->itpStay()->getId();

        $em = $this->em();
        $shift = $em->find(AgreementEnrollment::class, $enrollmentId)->getAgreement()->getShift();
        $shift->setStudentSurvey($this->createSurvey($em, 'FCT alumnado'))->setCompanySurvey($this->createSurvey($em, 'FCT empresa'));

        $agreement = $em->find(Agreement::class, $agreementId);
        $agreement->getProject()->setStudentSurvey($this->createSurvey($em, 'Dual alumnado'))
            ->setCompanySurvey($this->createSurvey($em, 'Dual empresa'));

        $stay = $em->find(StudentProgramWorkcenter::class, $stayId);
        $stay->getStudentProgram()->getProgramGroup()->getProgramGrade()->getTrainingProgram()
            ->setStudentSurvey($this->createSurvey($em, 'FFE alumnado'))->setCompanySurvey($this->createSurvey($em, 'FFE empresa'));

        $em->flush();
    }

    private function wptEnrollment(string $username): AgreementEnrollment
    {
        return $this->em()->getRepository(AgreementEnrollment::class)->createQueryBuilder('ae')
            ->join('ae.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult();
    }

    private function wltAgreement(): Agreement
    {
        return $this->em()->getRepository(Agreement::class)->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', 'alfa.alumno5')
            ->getQuery()->getSingleResult();
    }

    private function itpStay(): StudentProgramWorkcenter
    {
        return $this->em()->getRepository(StudentProgramWorkcenter::class)->createQueryBuilder('s')
            ->join('s.studentProgram', 'sp')->join('sp.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', 'alfa.alumno7')
            ->getQuery()->getSingleResult();
    }

    /**
     * @return array<string, string> URL de cada formulario de alumnado, por módulo
     */
    private function studentFormUrls(): array
    {
        return [
            'FCT' => '/fct/encuesta/estudiante/cumplimentar/' . $this->wptEnrollment('alfa.alumno1')->getId(),
            'FP dual' => '/dual/encuesta/estudiante/cumplimentar/' . $this->wltAgreement()->getId(),
            'FFE' => '/formacion/encuesta/estudiante/cumplimentar/' . $this->itpStay()->getId(),
        ];
    }

    /**
     * Contesta la primera pregunta con el formulario real de la página, incluido el botón y el token CSRF
     * (que deben estar dentro del elemento form)
     */
    private function submitSurvey(Crawler $crawler, string $value = '3'): void
    {
        $form = $crawler->selectButton('Guardar encuesta')->form();
        $form['answered_survey[answers][0][numericValue]']->select($value);

        $this->client->submit($form);
    }

    private function answeredSurveyCount(): int
    {
        return $this->em()->getRepository(AnsweredSurvey::class)->count([]);
    }

    /**
     * @return iterable<string, array{string, string}> módulo y usuario
     */
    public static function intruderProvider(): iterable
    {
        foreach (['FCT', 'FP dual', 'FFE'] as $module) {
            // el coordinador de FP dual no interviene en FCT ni en FFE, y el gestor de la FFE no interviene en FP dual
            yield "$module: gestor de otro módulo" => [$module, $module === 'FP dual' ? 'alfa.gestorffe' : 'alfa.coordinador'];
            yield "$module: responsable económico" => [$module, 'alfa.economico'];
            yield "$module: alumno de otro centro" => [$module, 'beta.alumno1'];
            yield "$module: administrador local de otro centro" => [$module, 'beta.admin'];
        }
        yield 'FCT: otro alumno del mismo acuerdo' => ['FCT', 'alfa.alumno2'];
        yield 'FP dual: alumno de FCT' => ['FP dual', 'alfa.alumno1'];
        yield 'FFE: alumno de FP dual' => ['FFE', 'alfa.alumno5'];
    }

    /**
     * @dataProvider intruderProvider
     */
    public function testIntrudersCannotOpenOrCreateTheStudentSurvey(string $module, string $username): void
    {
        $this->enableSurveys();
        $url = $this->studentFormUrls()[$module];
        $before = $this->answeredSurveyCount();
        $this->login($this->getUser($username));

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->answeredSurveyCount(), 'Una consulta no autorizada no debe crear encuestas');
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function participantProvider(): iterable
    {
        yield 'FCT: el propio alumno' => ['FCT', 'alfa.alumno1'];
        yield 'FCT: tutor docente' => ['FCT', 'alfa.tutor'];
        yield 'FCT: administrador local' => ['FCT', 'alfa.admin'];
        yield 'FP dual: el propio alumno' => ['FP dual', 'alfa.alumno5'];
        yield 'FP dual: tutor de grupo' => ['FP dual', 'alfa.tutor'];
        yield 'FP dual: coordinador' => ['FP dual', 'alfa.coordinador'];
        yield 'FFE: el propio alumno' => ['FFE', 'alfa.alumno7'];
        yield 'FFE: tutor docente' => ['FFE', 'alfa.profesor'];
        yield 'FFE: gestor de la FFE' => ['FFE', 'alfa.gestorffe'];
    }

    /**
     * @dataProvider participantProvider
     */
    public function testParticipantsOpenTheStudentSurvey(string $module, string $username): void
    {
        $this->enableSurveys();
        $this->login($this->getUser($username));

        $this->client->request('GET', $this->studentFormUrls()[$module]);

        self::assertResponseIsSuccessful();
    }

    public function testStudentAnswersTheirSurveyOnce(): void
    {
        $this->enableSurveys();
        $url = $this->studentFormUrls()['FCT'];
        $this->login($this->getUser('alfa.alumno1'));

        $crawler = $this->client->request('GET', $url);
        $this->submitSurvey($crawler, '4');
        self::assertResponseRedirects();
        self::assertNotNull($this->em()->getRepository(AnsweredSurveyQuestion::class)->findOneBy(['numericValue' => 4]));

        // abrir de nuevo el formulario no duplica la encuesta contestada
        $count = $this->answeredSurveyCount();
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();
        self::assertSame($count, $this->answeredSurveyCount());
    }

    public function testNoSurveyConfiguredShowsAReadOnlyPageInsteadOfFailing(): void
    {
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('GET', $this->studentFormUrls()['FCT']);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
    }

    // --- Encuestas de empresa --------------------------------------------------------------------

    /**
     * @return array<string, string>
     */
    private function workTutorFormUrls(): array
    {
        $workTutor = $this->getUser('alfa.tutorlaboral')->getId();

        return [
            'FCT' => '/fct/encuesta/empresa/cumplimentar/' . $this->wptEnrollment('alfa.alumno1')->getId() . '/' . $workTutor,
            'FP dual' => '/dual/encuesta/empresa/cumplimentar/' . $this->wltAgreement()->getId() . '/' . $workTutor,
            'FFE' => '/formacion/encuesta/empresa/cumplimentar/' . $this->itpStay()->getId() . '/' . $workTutor,
        ];
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function workTutorIntruderProvider(): iterable
    {
        foreach (['FCT', 'FP dual', 'FFE'] as $module) {
            yield "$module: alumno" => [$module, $module === 'FCT' ? 'alfa.alumno1' : ($module === 'FFE' ? 'alfa.alumno7' : 'alfa.alumno5')];
            yield "$module: tutor laboral de otro acuerdo" => [$module, 'alfa.tutorlaboral2'];
            yield "$module: responsable económico" => [$module, 'alfa.economico'];
            yield "$module: administrador local de otro centro" => [$module, 'beta.admin'];
        }
    }

    /**
     * @dataProvider workTutorIntruderProvider
     */
    public function testIntrudersCannotOpenOrCreateTheCompanySurvey(string $module, string $username): void
    {
        $this->enableSurveys();
        $url = $this->workTutorFormUrls()[$module];
        $before = $this->answeredSurveyCount();
        $this->login($this->getUser($username));

        $this->client->request('GET', $url);

        self::assertResponseStatusCodeSame(403);
        self::assertSame($before, $this->answeredSurveyCount());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function workTutorParticipantProvider(): iterable
    {
        foreach (['FCT', 'FP dual', 'FFE'] as $module) {
            yield "$module: tutor laboral" => [$module, 'alfa.tutorlaboral'];
            yield "$module: administrador local" => [$module, 'alfa.admin'];
        }
    }

    /**
     * @dataProvider workTutorParticipantProvider
     */
    public function testWorkTutorAnswersTheCompanySurvey(string $module, string $username): void
    {
        $this->enableSurveys();
        $this->login($this->getUser($username));

        $crawler = $this->client->request('GET', $this->workTutorFormUrls()[$module]);
        self::assertResponseIsSuccessful();
        $this->submitSurvey($crawler);

        self::assertResponseRedirects();
    }
}
