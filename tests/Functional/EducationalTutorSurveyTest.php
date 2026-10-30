<?php

namespace App\Tests\Functional;

use App\Entity\AnsweredSurvey;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\WltModule\Project;
use App\Entity\WptModule\Shift;
use App\Tests\Fixtures\DemoData;
use Doctrine\DBAL\Connection;

/**
 * Encuesta de centro de los tutores docentes de FCT, FP dual y FFE, y listados de las encuestas de cada módulo.
 * La contestan los tutores docentes del propio proyecto, convocatoria o programa, cada uno la suya, y en nombre de
 * cualquiera de ellos el jefe de departamento y la administración. Tutores docentes de las fixtures:
 *  - FCT: "tutor" (alumno1 y alumno2) y "profesor" (alumno3), en la misma convocatoria
 *  - FP dual: "tutor" (alumno5) y "profesor" (alumno6), en el mismo proyecto
 *  - FFE: "profesor" en la estancia de alumno7 y "tutor" en la de alumno8, del mismo programa
 */
class EducationalTutorSurveyTest extends AppWebTestCase
{
    use EnablesSurveysTrait;

    private function connection(): Connection
    {
        return static::getContainer()->get('doctrine')->getConnection();
    }

    private function teacherId(string $username): int
    {
        return (int) $this->connection()->fetchOne('SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', [$username]);
    }

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    /**
     * Estancia de FFE cuyo tutor docente es la persona indicada
     */
    private function stayOf(string $username): int
    {
        return (int) $this->connection()->fetchOne('SELECT id FROM itp_student_program_workcenter WHERE educational_tutor_id = ? ORDER BY id LIMIT 1', [$this->teacherId($username)]);
    }

    /**
     * Asigna una encuesta de centro a la convocatoria, al proyecto y al programa de las fixtures
     */
    private function enableEducationalTutorSurveys(): void
    {
        $shift = (int) $this->connection()->fetchOne('SELECT s.id FROM wpt_shift s JOIN edu_subject su ON su.id = s.subject_id JOIN edu_grade g ON g.id = su.grade_id JOIN edu_training t ON t.id = g.training_id JOIN edu_academic_year ay ON ay.id = t.academic_year_id WHERE ay.organization_id = ? ORDER BY s.id LIMIT 1', [$this->getOrganization()->getId()]);
        $project = (int) $this->connection()->fetchOne('SELECT id FROM wlt_project WHERE organization_id = ? ORDER BY id LIMIT 1', [$this->getOrganization()->getId()]);
        $program = (int) $this->connection()->fetchOne('SELECT pg.training_program_id FROM itp_student_program_workcenter w JOIN itp_student_program sp ON sp.id = w.student_program_id JOIN itp_program_group g ON g.id = sp.program_group_id JOIN itp_program_grade pg ON pg.id = g.program_grade_id WHERE w.id = ?', [$this->stayOf('alfa.profesor')]);

        $em = $this->em();
        $em->find(Shift::class, $shift)->setEducationalTutorSurvey($this->createSurvey($em, 'FCT centro'));
        $em->find(Project::class, $project)->setEducationalTutorSurvey($this->createSurvey($em, 'Dual centro'));
        $em->find(TrainingProgram::class, $program)->setEducationalTutorSurvey($this->createSurvey($em, 'FFE centro'));
        $em->flush();
        $em->clear();
    }

    /**
     * URL del formulario de la encuesta de centro de cada módulo para el profesor indicado
     *
     * @return array<string, string>
     */
    private function formUrls(string $teacher): array
    {
        $teacherId = $this->teacherId($teacher);
        $shift = (int) $this->connection()->fetchOne('SELECT a.shift_id FROM wpt_agreement a JOIN wpt_agreement_enrollment ae ON ae.agreement_id = a.id WHERE ae.educational_tutor_id = ? ORDER BY a.id LIMIT 1', [$this->teacherId('alfa.profesor')]);
        $project = (int) $this->connection()->fetchOne('SELECT project_id FROM wlt_agreement ORDER BY id LIMIT 1');

        return [
            'FCT' => "/fct/encuesta/centro/cumplimentar/$shift/$teacherId",
            'FP dual' => "/dual/encuesta/centro/cumplimentar/$project/$teacherId",
            'FFE' => '/formacion/encuesta/centro/cumplimentar/' . $this->stayOf('alfa.profesor') . "/$teacherId",
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function moduleProvider(): iterable
    {
        yield 'FCT' => ['FCT'];
        yield 'FP dual' => ['FP dual'];
        yield 'FFE' => ['FFE'];
    }

    /**
     * @dataProvider moduleProvider
     */
    public function testTutorAnswersTheirOwnSurvey(string $module): void
    {
        $this->enableEducationalTutorSurveys();
        $this->login($this->getUser('alfa.profesor'));
        $before = $this->em()->getRepository(AnsweredSurvey::class)->count([]);

        $crawler = $this->client->request('GET', $this->formUrls('alfa.profesor')[$module]);
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Guardar encuesta')->form();
        $form['answered_survey[answers][0][numericValue]']->select('4');
        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertSame($before + 1, $this->em()->getRepository(AnsweredSurvey::class)->count([]));
    }

    /**
     * @dataProvider moduleProvider
     */
    public function testTutorCannotAnswerOnBehalfOfAnotherTeacher(string $module): void
    {
        $this->enableEducationalTutorSurveys();
        $before = $this->em()->getRepository(AnsweredSurvey::class)->count([]);
        $this->login($this->getUser('alfa.profesor'));

        // alfa.tutor y alfa.profesor son tutores docentes de FCT y FP dual; en FFE el primero lo es de otra estancia
        $crawler = $this->client->request('GET', $this->formUrls('alfa.tutor')[$module]);
        if ($this->client->getResponse()->isSuccessful() && $crawler->selectButton('Guardar encuesta')->count() > 0) {
            $form = $crawler->selectButton('Guardar encuesta')->form();
            $form['answered_survey[answers][0][numericValue]']->select('1');
            $this->client->submit($form);
        }

        self::assertSame($before, $this->em()->getRepository(AnsweredSurvey::class)->count([]), 'No se contesta la encuesta de otro tutor');
        $this->client->request('GET', $this->formUrls('alfa.tutor')[$module]);
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @dataProvider moduleProvider
     */
    public function testHeadAndAdministratorCanOpenTheSurveyOfATutor(string $module): void
    {
        $this->enableEducationalTutorSurveys();

        foreach (['alfa.jefe', 'alfa.admin'] as $username) {
            $this->login($this->getUser($username));
            $this->client->request('GET', $this->formUrls('alfa.profesor')[$module]);
            self::assertResponseIsSuccessful($username . ' ' . $module);
        }
    }

    /**
     * @dataProvider moduleProvider
     */
    public function testOthersCannotOpenTheSurvey(string $module): void
    {
        $this->enableEducationalTutorSurveys();
        $urls = $this->formUrls('alfa.profesor');
        $jefeAsTeacher = $this->formUrls('alfa.jefe')[$module];

        foreach (['alfa.alumno1', 'alfa.tutorlaboral', 'alfa.economico', 'alfa.director'] as $username) {
            $this->login($this->getUser($username));
            $this->client->request('GET', $urls[$module]);
            self::assertResponseStatusCodeSame(403, $username . ' ' . $module);
        }

        // un profesor que no es tutor docente no tiene encuesta que contestar
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', $jefeAsTeacher);
        self::assertResponseStatusCodeSame(403);

        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->client->request('GET', $urls[$module]);
        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return iterable<string, array{string, string, int}> prefijo de las rutas, usuario y código esperado
     */
    public static function listProvider(): iterable
    {
        $modules = [
            'FCT' => ['/fct/encuesta', 'alfa.alumno1'],
            'FP dual' => ['/dual/encuesta', 'alfa.alumno5'],
            'FFE' => ['/formacion/encuesta', 'alfa.alumno7'],
        ];
        foreach ($modules as $module => [$base, $student]) {
            yield "$module: alumno" => [$base . '/estudiante', $student, 200];
            yield "$module: tutor laboral" => [$base . '/empresa', 'alfa.tutorlaboral', 200];
            yield "$module: tutor docente" => [$base . '/centro', 'alfa.profesor', 200];
            yield "$module: administrador" => [$base . '/centro', 'alfa.admin', 200];
            yield "$module: alumno no ve la de centro" => [$base . '/centro', $student, 403];
            yield "$module: economico no ve la de alumnado" => [$base . '/estudiante', 'alfa.economico', 403];
        }
        // en FCT y FFE el jefe de departamento ve el listado de centro; en FP dual solo llega al formulario
        yield 'FCT: jefe de departamento' => ['/fct/encuesta/centro', 'alfa.jefe', 200];
        yield 'FFE: jefe de departamento' => ['/formacion/encuesta/centro', 'alfa.jefe', 200];
    }

    /**
     * @dataProvider listProvider
     */
    public function testSurveyLists(string $base, string $username, int $status): void
    {
        $this->enableEducationalTutorSurveys();
        $this->enableSurveys();
        $this->login($this->getUser($username));

        foreach (['', '?q=zzzz-no-existe'] as $query) {
            $this->client->request('GET', $base . '/' . $this->year() . '/1' . $query);
            self::assertResponseStatusCodeSame($status, $username . ' ' . $base . $query);
        }
    }

    /**
     * @dataProvider moduleProvider
     */
    public function testBackLinkReturnsToTheListOfTheAcademicYear(string $module): void
    {
        $this->enableEducationalTutorSurveys();
        $this->login($this->getUser('alfa.profesor'));
        $list = ['FCT' => '/fct/encuesta/centro/', 'FP dual' => '/dual/encuesta/centro/', 'FFE' => '/formacion/encuesta/centro/'][$module];

        $crawler = $this->client->request('GET', $this->formUrls('alfa.profesor')[$module]);

        $links = $crawler->filter('a[href^="' . $list . '"]')->each(static fn($a) => $a->attr('href'));
        self::assertContains($list . $this->year(), $links);
    }
}
