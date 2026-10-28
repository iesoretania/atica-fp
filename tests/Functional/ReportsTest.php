<?php

namespace App\Tests\Functional;

use App\Tests\Fixtures\DemoData;

/**
 * Informes de FCT, FP dual y FFE (PDF y estadísticas de encuestas): quién puede pedirlos y qué devuelven.
 * Las encuestas de las fixtures no existen, por lo que se crean al principio de cada test.
 */
class ReportsTest extends AppWebTestCase
{
    use EnablesSurveysTrait;

    /**
     * @return array<string, string> URL de cada informe, por nombre
     */
    private function reportUrls(): array
    {
        $ids = $this->em()->getConnection()->fetchAssociative(
            'SELECT (SELECT id FROM wpt_shift LIMIT 1) AS shift, (SELECT id FROM wlt_project LIMIT 1) AS project, (SELECT id FROM itp_training_program LIMIT 1) AS program'
        );
        $year = $this->getOrganization()->getCurrentAcademicYear()->getId();

        return [
            'FCT encuesta de alumnado' => "/fct/informe/encuesta/estudiantes/{$ids['shift']}",
            'FCT encuesta de empresas' => "/fct/informe/encuesta/empresas/{$ids['shift']}",
            'FCT encuesta de centro' => "/fct/informe/encuesta/centro/{$ids['shift']}",
            'FP dual reuniones' => "/dual/informe/reuniones/{$ids['project']}/$year",
            'FP dual evaluación' => "/dual/informe/evaluacion/{$ids['project']}/$year",
            'FP dual asistencia' => "/dual/informe/asistencia/{$ids['project']}/$year",
            'FP dual programa formativo' => "/dual/informe/programa_formativo/{$ids['project']}/$year",
            'FP dual encuesta de alumnado' => "/dual/informe/encuesta/estudiantes/{$ids['project']}/$year",
            'FP dual encuesta de empresas' => "/dual/informe/encuesta/empresas/{$ids['project']}/$year",
            'FP dual encuesta de centro' => "/dual/informe/encuesta/centro/{$ids['project']}/$year",
            'FFE encuesta de alumnado' => "/formacion/informe/encuesta/estudiantes/{$ids['program']}/$year",
            'FFE encuesta de empresas' => "/formacion/informe/encuesta/empresas/{$ids['program']}/$year",
            'FFE encuesta de centro' => "/formacion/informe/encuesta/centro/{$ids['program']}/$year",
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
    public function testManagersGetEveryReport(string $username): void
    {
        $this->enableSurveys();
        $this->login($this->getUser($username));

        foreach ($this->reportUrls() as $name => $url) {
            $this->client->request('GET', $url);
            self::assertResponseIsSuccessful("$username: $name");
        }
    }

    public function testDualReportsAreRealPdfFiles(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach (['FP dual reuniones', 'FP dual evaluación', 'FP dual asistencia', 'FP dual programa formativo'] as $name) {
            $this->client->request('GET', $this->reportUrls()[$name]);
            self::assertResponseIsSuccessful($name);
            self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'), $name);
            self::assertStringStartsWith('%PDF-', $this->client->getInternalResponse()->getContent(), $name);
            self::assertStringContainsString('inline', (string) $this->client->getResponse()->headers->get('Content-Disposition'));
        }
    }

    public function testSurveyReportsOfDualAreGeneratedWhenTheSurveyExists(): void
    {
        $this->enableSurveys();
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));

        foreach (['FP dual encuesta de alumnado', 'FP dual encuesta de empresas'] as $name) {
            $this->client->request('GET', $this->reportUrls()[$name]);
            self::assertResponseIsSuccessful($name);
            self::assertSame('application/pdf', $this->client->getResponse()->headers->get('Content-Type'), $name);
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function outsiderProvider(): iterable
    {
        // el coordinador de FP dual y el gestor de la FFE son responsables de sus módulos: ver testModuleManagers...
        foreach (['tutor', 'profesor', 'tutorlaboral', 'alumno1', 'alumno5', 'alumno7', 'coordinador', 'gestorffe', 'economico'] as $role) {
            yield $role => ['alfa.' . $role];
        }
    }

    /**
     * @dataProvider outsiderProvider
     */
    public function testOthersCannotGetAnyReport(string $username): void
    {
        $this->enableSurveys();
        $this->login($this->getUser($username));

        // cada responsable de módulo ve los informes de su módulo y ninguno de los demás
        $ownModule = ['alfa.coordinador' => 'FP dual', 'alfa.gestorffe' => 'FFE'][$username] ?? null;
        foreach ($this->reportUrls() as $name => $url) {
            $this->client->request('GET', $url);
            if ($ownModule !== null && str_starts_with($name, $ownModule)) {
                self::assertLessThan(500, $this->client->getResponse()->getStatusCode(), "$username: $name");
                continue;
            }
            self::assertResponseStatusCodeSame(403, "$username: $name");
        }
    }

    public function testModuleManagersGetTheReportsOfTheirModule(): void
    {
        $this->enableSurveys();
        $urls = $this->reportUrls();

        $this->login($this->getUser('alfa.coordinador'));
        foreach (['FP dual reuniones', 'FP dual evaluación', 'FP dual asistencia', 'FP dual programa formativo', 'FP dual encuesta de alumnado'] as $name) {
            $this->client->request('GET', $urls[$name]);
            self::assertResponseIsSuccessful("coordinador: $name");
        }

        $this->login($this->getUser('alfa.gestorffe'));
        foreach (['FFE encuesta de alumnado', 'FFE encuesta de empresas'] as $name) {
            $this->client->request('GET', $urls[$name]);
            self::assertResponseIsSuccessful("gestor de la FFE: $name");
        }
    }

    public function testAdministratorOfAnotherOrganizationCannotGetAnyReport(): void
    {
        $this->enableSurveys();
        $urls = $this->reportUrls();
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach ($urls as $name => $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $name);
        }
    }

    public function testAnonymousUsersAreSentToTheLogin(): void
    {
        foreach ($this->reportUrls() as $name => $url) {
            $this->client->request('GET', $url);
            self::assertResponseRedirects(null, null, $name);
        }
    }
}
