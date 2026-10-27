<?php

namespace App\Tests\Functional;

use App\Entity\Edu\AcademicYear;
use App\Tests\Fixtures\DemoData;

/**
 * Pantallas de la estructura académica (/centro/...) según el rol del usuario y la organización
 */
class EduPagesTest extends AppWebTestCase
{
    private function academicYearId(string $organizationCode): int
    {
        $organization = $this->getOrganization($organizationCode);
        $academicYear = static::getContainer()->get('doctrine')->getRepository(AcademicYear::class)
            ->findOneBy(['organization' => $organization]);
        self::assertInstanceOf(AcademicYear::class, $academicYear);

        return $academicYear->getId();
    }

    /**
     * Listados de la organización y un texto que debe aparecer en ellos. El segundo elemento
     * indica si la ruta recibe el curso académico como parámetro.
     *
     * @return iterable<string, array{string, bool, string}>
     */
    public static function listProvider(): iterable
    {
        yield 'departamentos' => ['organization_department_list', true, 'Informática'];
        yield 'niveles' => ['organization_grade_list', true, '1º DAM'];
        yield 'grupos' => ['organization_group_list', true, '1º DAM A'];
        yield 'enseñanzas' => ['organization_training_list', true, 'Desarrollo de Aplicaciones Multiplataforma'];
        yield 'materias' => ['organization_subject_list', true, 'Bases de datos'];
        yield 'profesorado' => ['organization_teacher_list', true, 'Docente Alfa'];
        yield 'matrículas' => ['organization_student_enrollment_list', true, 'Estudiante Alfa'];
        yield 'días no lectivos' => ['organization_non_working_day_list', true, 'Navidad'];
        yield 'vías de contacto' => ['organization_contact_method_list', true, 'Correo electrónico'];
        yield 'escalas' => ['organization_performance_scale_list', false, 'Escala de desempeño'];
        yield 'itinerarios' => ['organization_travel_route_list', false, 'Linares - Jaén'];
    }

    private function url(string $route, bool $withAcademicYear, string $organizationCode): string
    {
        $parameters = $withAcademicYear ? ['academicYear' => $this->academicYearId($organizationCode)] : [];

        return static::getContainer()->get('router')->generate($route, $parameters);
    }

    /**
     * @dataProvider listProvider
     */
    public function testLocalAdministratorSeesOwnOrganizationData(string $route, bool $withAcademicYear, string $expected): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', $this->url($route, $withAcademicYear, DemoData::ORG_A_CODE));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString($expected, $this->client->getResponse()->getContent());
    }

    /**
     * @dataProvider listProvider
     */
    public function testLocalAdministratorCannotAccessOtherOrganizationData(string $route, bool $withAcademicYear): void
    {
        if (!$withAcademicYear) {
            // estas pantallas no reciben el curso académico: usan siempre la organización de la sesión
            $this->markTestSkipped('La organización se toma de la sesión, no de la URL');
        }
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
        $this->client->request('GET', $this->url($route, true, DemoData::ORG_B_CODE));

        self::assertContains($this->client->getResponse()->getStatusCode(), [403, 404], $route);
    }

    /**
     * @dataProvider listProvider
     */
    public function testOrdinaryTeacherCannotManageTheOrganizationStructure(string $route, bool $withAcademicYear): void
    {
        $this->login($this->getUser(DemoData::USER_A_TEACHER));
        $this->client->request('GET', $this->url($route, $withAcademicYear, DemoData::ORG_A_CODE));

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @dataProvider listProvider
     */
    public function testOtherOrganizationAdministratorSeesOnlyOwnData(string $route, bool $withAcademicYear): void
    {
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);
        $this->client->request('GET', $this->url($route, $withAcademicYear, DemoData::ORG_B_CODE));

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('Alfa', $this->client->getResponse()->getContent());
    }
}
