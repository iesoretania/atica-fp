<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\TrainingProgram;
use App\Entity\WltModule\Project;
use App\Entity\WptModule\Shift;
use App\Tests\Fixtures\DemoData;

/**
 * Índices y listados de informes de FCT, FP dual y FFE, y estadísticas de las encuestas con respuestas: el
 * alumnado, los tutores laborales y los tutores docentes contestan sus encuestas y los gestores obtienen el PDF.
 */
class ReportListsAndSurveyStatsTest extends AppWebTestCase
{
    use EnablesSurveysTrait;

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    /**
     * Asigna también una encuesta de centro a la convocatoria, al proyecto y al programa
     */
    private function enableAllSurveys(): void
    {
        $this->enableSurveys();
        $shift = $this->wptEnrollment('alfa.alumno1')->getAgreement()->getShift()->getId();
        $project = $this->wltAgreement()->getProject()->getId();
        $program = $this->itpStay()->getStudentProgram()->getProgramGroup()->getProgramGrade()->getTrainingProgram()->getId();

        $em = $this->em();
        $em->find(Shift::class, $shift)->setEducationalTutorSurvey($this->createSurvey($em, 'FCT centro'));
        $em->find(Project::class, $project)->setEducationalTutorSurvey($this->createSurvey($em, 'Dual centro'));
        $em->find(TrainingProgram::class, $program)->setEducationalTutorSurvey($this->createSurvey($em, 'FFE centro'));
        $em->flush();
        $em->clear();
    }

    private function answer(string $username, string $url): void
    {
        $this->login($this->getUser($username));
        $crawler = $this->client->request('GET', $url);
        self::assertResponseIsSuccessful("$username $url");
        $form = $crawler->selectButton('Guardar encuesta')->form();
        $form['answered_survey[answers][0][numericValue]']->select('4');
        $this->client->submit($form);
        self::assertResponseRedirects(null, null, "$username $url");
    }

    private function teacherId(string $username): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT t.id FROM edu_teacher t JOIN person p ON p.id = t.person_id WHERE p.login_username = ?', [$username]);
    }

    /**
     * @return array<string, array{string, string}> URL de la encuesta, usuario que la contesta e identificador de la entidad
     */
    private function answerForms(): array
    {
        $conn = $this->em()->getConnection();
        $workTutor = $this->getUser('alfa.tutorlaboral')->getId();
        $tutor = $this->teacherId('alfa.profesor');
        $enrollment = $this->wptEnrollment('alfa.alumno1')->getId();
        $shift = (int) $conn->fetchOne('SELECT a.shift_id FROM wpt_agreement a JOIN wpt_agreement_enrollment ae ON ae.agreement_id = a.id WHERE ae.educational_tutor_id = ? ORDER BY a.id LIMIT 1', [$tutor]);
        $agreement = $this->wltAgreement()->getId();
        $project = (int) $conn->fetchOne('SELECT project_id FROM wlt_agreement ORDER BY id LIMIT 1');
        $stay = $this->itpStay()->getId();
        $tutorStay = (int) $conn->fetchOne('SELECT id FROM itp_student_program_workcenter WHERE educational_tutor_id = ? ORDER BY id LIMIT 1', [$tutor]);

        return [
            ['/fct/encuesta/estudiante/cumplimentar/' . $enrollment, 'alfa.alumno1', ''],
            ['/fct/encuesta/empresa/cumplimentar/' . $enrollment . '/' . $workTutor, 'alfa.tutorlaboral', ''],
            ["/fct/encuesta/centro/cumplimentar/$shift/$tutor", 'alfa.profesor', ''],
            ["/dual/encuesta/estudiante/cumplimentar/$agreement", 'alfa.alumno5', ''],
            ["/dual/encuesta/empresa/cumplimentar/$agreement/$workTutor", 'alfa.tutorlaboral', ''],
            ["/dual/encuesta/centro/cumplimentar/$project/$tutor", 'alfa.profesor', ''],
            ["/formacion/encuesta/estudiante/cumplimentar/$stay", 'alfa.alumno7', ''],
            ["/formacion/encuesta/empresa/cumplimentar/$stay/$workTutor", 'alfa.tutorlaboral', ''],
            ["/formacion/encuesta/centro/cumplimentar/$tutorStay/$tutor", 'alfa.profesor', ''],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function surveyReportUrls(): array
    {
        $ids = $this->em()->getConnection()->fetchAssociative(
            'SELECT (SELECT id FROM wpt_shift LIMIT 1) AS shift, (SELECT id FROM wlt_project LIMIT 1) AS project, (SELECT id FROM itp_training_program LIMIT 1) AS program'
        );
        $year = $this->year();

        return [
            'FCT alumnado' => "/fct/informe/encuesta/estudiantes/{$ids['shift']}",
            'FCT empresas' => "/fct/informe/encuesta/empresas/{$ids['shift']}",
            'FCT centro' => "/fct/informe/encuesta/centro/{$ids['shift']}",
            'FP dual alumnado' => "/dual/informe/encuesta/estudiantes/{$ids['project']}/$year",
            'FP dual empresas' => "/dual/informe/encuesta/empresas/{$ids['project']}/$year",
            'FP dual centro' => "/dual/informe/encuesta/centro/{$ids['project']}/$year",
            'FFE alumnado' => "/formacion/informe/encuesta/estudiantes/{$ids['program']}/$year",
            'FFE empresas' => "/formacion/informe/encuesta/empresas/{$ids['program']}/$year",
            'FFE centro' => "/formacion/informe/encuesta/centro/{$ids['program']}/$year",
        ];
    }

    public function testSurveyReportsContainTheAnswers(): void
    {
        $this->enableAllSurveys();
        foreach ($this->answerForms() as [$url, $username]) {
            $this->answer($username, $url);
        }

        foreach (['alfa.admin', 'alfa.jefe'] as $username) {
            $this->login($this->getUser($username));
            foreach ($this->surveyReportUrls() as $name => $url) {
                $this->client->request('GET', $url);
                self::assertResponseIsSuccessful("$username $name");
                self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'), "$username $name");
            }
        }
    }

    /**
     * @return array<string, list<string>> prefijo de cada módulo y sus listados de informes
     */
    private function lists(): array
    {
        $year = $this->year();
        $surveys = ['encuesta/estudiantes', 'encuesta/empresas', 'encuesta/centro'];

        return [
            'FCT' => array_map(static fn(string $s): string => "/fct/informe/$s/listar/$year/1", $surveys),
            'FP dual' => array_map(
                static fn(string $s): string => "/dual/informe/$s/listar/$year/1",
                [...$surveys, 'reuniones', 'asistencia', 'evaluacion', 'programa_formativo']
            ),
            'FFE' => array_map(static fn(string $s): string => "/formacion/informe/$s/listar/$year/1", $surveys),
        ];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function managerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'jefe de departamento' => ['alfa.jefe'];
    }

    /**
     * @dataProvider managerProvider
     */
    public function testManagersOpenTheIndexesAndLists(string $username): void
    {
        $this->login($this->getUser($username));

        foreach (['/fct/informe/', '/dual/informe/', '/formacion/informe/'] as $index) {
            $this->client->request('GET', $index);
            self::assertResponseIsSuccessful("$username $index");
        }
        foreach ($this->lists() as $module => $urls) {
            foreach ($urls as $url) {
                foreach (['', '?q=zzzz-no-existe'] as $query) {
                    $this->client->request('GET', $url . $query);
                    self::assertResponseIsSuccessful("$username $module $url$query");
                }
            }
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        foreach (['alumno1', 'tutorlaboral', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotOpenTheLists(string $username): void
    {
        $this->login($this->getUser($username));

        foreach ($this->lists() as $module => $urls) {
            foreach ($urls as $url) {
                $this->client->request('GET', $url);
                self::assertResponseStatusCodeSame(403, "$username $module $url");
            }
        }
    }

    public function testAdministratorOfAnotherCentreCannotOpenThem(): void
    {
        $lists = $this->lists();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($lists as $module => $urls) {
            foreach ($urls as $url) {
                $this->client->request('GET', $url);
                self::assertResponseStatusCodeSame(403, "$module $url");
            }
        }
    }
}
