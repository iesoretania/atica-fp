<?php

namespace App\Tests\Functional;

use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Report;
use App\Entity\WptModule\Shift;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Informe final de la empresa sobre el alumnado de FCT: listado, formulario y descarga en PDF.
 *  - convenio 1: alumno1 y alumno2 (tutor docente y de grupo "tutor", tutor laboral "tutorlaboral")
 *  - convenio 2: alumno3 (tutor docente "profesor", tutor laboral "tutorlaboral2")
 */
class WptFinalReportTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function enrollmentId(string $username = 'alfa.alumno1'): int
    {
        return $this->em()->getRepository(AgreementEnrollment::class)->createQueryBuilder('ae')
            ->join('ae.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult()->getId();
    }

    private function reportCount(): int
    {
        return $this->em()->getRepository(Report::class)->count([]);
    }

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    /**
     * Rellena y envía el formulario del informe con datos válidos
     */
    private function fillReport(int $enrollment, string $activities = 'Tareas realizadas en la empresa'): void
    {
        $crawler = $this->client->request('GET', "/fct/informe/cumplimentar/$enrollment");
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar cambios')->form(['final_report[workActivities]' => $activities]);
        foreach (['professionalCompetence', 'organizationalCompetence', 'relationalCompetence', 'contingencyResponse'] as $field) {
            $values = $form["final_report[$field]"]->availableOptionValues();
            $form["final_report[$field]"]->select((string) end($values));
        }
        $this->client->submit($form);
    }

    private function closeShift(): void
    {
        $em = $this->em();
        $em->getRepository(Shift::class)->findOneBy([])->setLocked(true);
        $em->flush();
        $em->clear();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function reportersProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'tutor docente y de grupo' => ['alfa.tutor'];
        yield 'tutor laboral' => ['alfa.tutorlaboral'];
    }

    /**
     * @dataProvider reportersProvider
     */
    public function testAuthorizedPeopleSeeTheListAndTheForm(string $username): void
    {
        $enrollment = $this->enrollmentId();
        $this->login($this->getUser($username));

        $this->client->request('GET', '/fct/informe/acuerdo/' . $this->year());
        self::assertResponseIsSuccessful();
        $this->client->request('GET', "/fct/informe/cumplimentar/$enrollment");
        self::assertResponseIsSuccessful();
    }

    /**
     * @dataProvider reportersProvider
     */
    public function testAuthorizedPeopleFillTheReportAndDownloadIt(string $username): void
    {
        $enrollment = $this->enrollmentId();
        $before = $this->reportCount();
        $this->login($this->getUser($username));

        $this->fillReport($enrollment);

        self::assertResponseRedirects();
        self::assertSame($before + 1, $this->reportCount());
        $this->client->request('GET', "/fct/informe/descargar/$enrollment");
        self::assertResponseIsSuccessful();
        self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'));
        self::assertStringStartsWith('%PDF-', $this->client->getInternalResponse()->getContent());
    }

    public function testEditingTheReportAgainDoesNotCreateAnotherOne(): void
    {
        $enrollment = $this->enrollmentId();
        $this->login($this->getUser('alfa.tutor'));
        $this->fillReport($enrollment, 'Primera versión');
        $count = $this->reportCount();

        $this->fillReport($enrollment, 'Versión corregida');

        self::assertSame($count, $this->reportCount());
        self::assertSame('Versión corregida', $this->em()->getRepository(AgreementEnrollment::class)->find($enrollment)->getReport()->getWorkActivities());
    }

    public function testIncompleteReportIsRejected(): void
    {
        $enrollment = $this->enrollmentId();
        $before = $this->reportCount();
        $this->login($this->getUser('alfa.tutor'));

        $crawler = $this->client->request('GET', "/fct/informe/cumplimentar/$enrollment");
        $this->client->submit($crawler->selectButton('Guardar cambios')->form(['final_report[workActivities]' => '']));

        self::assertResponseStatusCodeSame(200);
        self::assertSame($before, $this->reportCount());
    }

    public function testDownloadWithoutAReportIsNotFound(): void
    {
        $enrollment = $this->enrollmentId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', "/fct/informe/descargar/$enrollment");

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        yield 'el propio alumno' => ['alfa.alumno1'];
        yield 'tutor laboral de otro convenio' => ['alfa.tutorlaboral2'];
        yield 'tutor docente de otro convenio' => ['alfa.profesor'];
        yield 'coordinador de FP dual' => ['alfa.coordinador'];
        yield 'responsable económico' => ['alfa.economico'];
        yield 'administrador local de otro centro' => ['beta.admin'];
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOutsidersCannotSeeFillOrDownloadTheReport(string $username): void
    {
        $enrollment = $this->enrollmentId();
        $this->login($this->getUser('alfa.tutor'));
        $this->fillReport($enrollment);
        $count = $this->reportCount();
        $this->login($this->getUser($username), true, str_starts_with($username, 'beta.') ? DemoData::ORG_B_CODE : DemoData::ORG_A_CODE);

        $this->client->request('GET', "/fct/informe/cumplimentar/$enrollment");
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', "/fct/informe/cumplimentar/$enrollment", ['final_report' => ['workActivities' => 'Texto ajeno']]);
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', "/fct/informe/descargar/$enrollment");
        self::assertResponseStatusCodeSame(403);

        self::assertSame($count, $this->reportCount());
        self::assertSame('Tareas realizadas en la empresa', $this->em()->getRepository(AgreementEnrollment::class)->find($enrollment)->getReport()->getWorkActivities());
    }

    public function testStudentCannotUseTheList(): void
    {
        $this->login($this->getUser('alfa.alumno1'));

        $this->client->request('GET', '/fct/informe/acuerdo/' . $this->year());

        self::assertResponseStatusCodeSame(403);
    }

    public function testListIsFilteredByRole(): void
    {
        $this->login($this->getUser('alfa.tutorlaboral2'));

        $this->client->request('GET', '/fct/informe/acuerdo/' . $this->year());

        $content = (string) $this->client->getResponse()->getContent();
        self::assertStringContainsString('Alumno 3', $content);
        self::assertStringNotContainsString('Alumno 1', $content);
    }

    // --- Convenio cerrado -----------------------------------------------------------------------------

    public function testWhenTheShiftIsClosedTheReportIsReadOnlyAndNothingIsSaved(): void
    {
        $enrollment = $this->enrollmentId();
        $this->login($this->getUser('alfa.tutor'));
        $this->fillReport($enrollment, 'Versión original');
        $count = $this->reportCount();
        $this->closeShift();

        $this->client->request('GET', "/fct/informe/cumplimentar/$enrollment");
        self::assertResponseIsSuccessful();
        $crawler = $this->client->getCrawler();
        self::assertSame(0, $crawler->selectButton('Guardar cambios')->count(), 'Sin botón de guardar');

        $this->client->request('POST', "/fct/informe/cumplimentar/$enrollment", ['final_report' => ['workActivities' => 'Cambio con la convocatoria cerrada']]);

        self::assertSame($count, $this->reportCount());
        self::assertSame('Versión original', $this->em()->getRepository(AgreementEnrollment::class)->find($enrollment)->getReport()->getWorkActivities());
    }

    public function testReadOnlyViewDoesNotCreateAnEmptyReport(): void
    {
        $enrollment = $this->enrollmentId();
        $before = $this->reportCount();
        $this->closeShift();
        $this->login($this->getUser('alfa.tutor'));

        $this->client->request('GET', "/fct/informe/cumplimentar/$enrollment");
        $this->client->request('POST', "/fct/informe/cumplimentar/$enrollment", ['final_report' => ['workActivities' => 'Sin permiso']]);

        self::assertSame($before, $this->reportCount(), 'Una consulta o un envío sin permiso no crea informes');
    }
}
