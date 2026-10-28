<?php

namespace App\Tests\Functional;

use App\Entity\AnsweredSurveyQuestion;
use App\Tests\Fixtures\DemoData;

/**
 * Informes de la FFE en PDF: estadísticas de las encuestas con respuestas reales y valoración y evaluación de una
 * estancia. Estancia de alumno7: tutor de grupo "tutor", tutor docente "profesor", tutor laboral "tutorlaboral",
 * gestor del grupo "gestorffe".
 */
class ItpReportsTest extends AppWebTestCase
{
    use EnablesSurveysTrait;

    private function programId(): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT id FROM itp_training_program LIMIT 1');
    }

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    /**
     * Rellena y envía el primer formulario de encuesta de la página con la valoración indicada
     */
    private function answerSurvey(string $username, string $url, string $value): void
    {
        $this->login($this->getUser($username));
        $crawler = $this->client->request('GET', $url);
        self::assertResponseIsSuccessful($url);
        $form = $crawler->selectButton('Guardar encuesta')->form();
        $form['answered_survey[answers][0][numericValue]']->select($value);
        $this->client->submit($form);
        self::assertResponseRedirects();
    }

    private function answerBothSurveys(): void
    {
        $stay = $this->itpStay()->getId();
        $workTutor = $this->getUser('alfa.tutorlaboral')->getId();
        $this->answerSurvey('alfa.alumno7', "/formacion/encuesta/estudiante/cumplimentar/$stay", '4');
        $this->answerSurvey('alfa.tutorlaboral', "/formacion/encuesta/empresa/cumplimentar/$stay/$workTutor", '5');
    }

    public function testSurveyReportsAreGeneratedWithTheRealAnswers(): void
    {
        $this->enableSurveys();
        $program = $this->programId();
        $year = $this->year();
        $this->answerBothSurveys();
        self::assertSame(2, $this->em()->getRepository(AnsweredSurveyQuestion::class)->count([]) - $this->em()->getRepository(AnsweredSurveyQuestion::class)->count(['numericValue' => null]));

        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        foreach (['estudiantes', 'empresas'] as $report) {
            $this->client->request('GET', "/formacion/informe/encuesta/$report/$program/$year");

            self::assertResponseIsSuccessful($report);
            self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'), $report);
            self::assertStringStartsWith('%PDF-', $this->client->getInternalResponse()->getContent(), $report);
        }
    }

    public function testSurveyReportWithoutSurveyShowsANoticeInsteadOfAPdf(): void
    {
        $program = $this->programId();
        $year = $this->year();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        $this->client->request('GET', "/formacion/informe/encuesta/estudiantes/$program/$year");

        self::assertResponseIsSuccessful();
        self::assertStringStartsWith('text/html', (string) $this->client->getResponse()->headers->get('Content-Type'));
    }

    public function testSurveyReportsOfOtherYearsOrWithManyDigitsDoNotFail(): void
    {
        $this->enableSurveys();
        $program = $this->programId();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach (['estudiantes', 'empresas', 'centro'] as $report) {
            // un curso que no existe, y uno de varias cifras como los de una base de datos en uso
            foreach ([999999, 10, 123] as $year) {
                $this->client->request('GET', "/formacion/informe/encuesta/$report/$program/$year");
                self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), "$report/$year");
            }
        }
    }

    /**
     * @return iterable<string, array{string, bool, bool}> usuario, valoración, evaluación
     */
    public static function gradingReportProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin', true, true];
        yield 'jefe de departamento' => ['alfa.jefe', true, true];
        yield 'gestor de la FFE' => ['alfa.gestorffe', true, true];
        yield 'tutor docente' => ['alfa.profesor', true, true];
        yield 'tutor de grupo' => ['alfa.tutor', true, true];
        yield 'tutor laboral' => ['alfa.tutorlaboral', true, false];
        yield 'alumno de la estancia' => ['alfa.alumno7', false, false];
        yield 'tutor laboral de otra estancia' => ['alfa.tutorlaboral2', false, false];
        yield 'coordinador de FP dual' => ['alfa.coordinador', false, false];
        yield 'responsable económico' => ['alfa.economico', false, false];
        yield 'alumno de FCT' => ['alfa.alumno1', false, false];
    }

    /**
     * @dataProvider gradingReportProvider
     */
    public function testGradingAndEvaluationReportsFollowTheRoleOfTheUser(string $username, bool $grading, bool $evaluation): void
    {
        $stay = $this->itpStay()->getId();
        $this->login($this->getUser($username));

        foreach (['valoración' => ["/formacion/valoracion/informe/$stay", $grading], 'evaluación' => ["/formacion/valoracion/evaluacion/informe/$stay", $evaluation]] as $name => [$url, $allowed]) {
            $this->client->request('GET', $url);
            if ($allowed) {
                self::assertResponseIsSuccessful("$username: $name");
                self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'), "$username: $name");
                self::assertStringStartsWith('%PDF-', $this->client->getInternalResponse()->getContent(), "$username: $name");
            } else {
                self::assertResponseStatusCodeSame(403, "$username: $name");
            }
        }
    }

    public function testAdministratorOfAnotherOrganizationCannotGetAnyReport(): void
    {
        $this->enableSurveys();
        $stay = $this->itpStay()->getId();
        $program = $this->programId();
        $year = $this->year();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ([
            "/formacion/valoracion/informe/$stay",
            "/formacion/valoracion/evaluacion/informe/$stay",
            "/formacion/informe/encuesta/estudiantes/$program/$year",
            "/formacion/informe/encuesta/empresas/$program/$year",
        ] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }
}
