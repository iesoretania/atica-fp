<?php

namespace App\Tests\Functional;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Listados de valoración y evaluación de la FFE y calificación de las actividades de una estancia.
 *  - alumno7: tutor docente "profesor", tutor laboral "tutorlaboral"; alumno8: tutor docente "tutor", tutor laboral
 *    "tutorlaboral2"; el gestor "gestorffe" gestiona el grupo de ambos, del que "tutor" es además tutor de grupo
 */
class ItpGradingListsTest extends AppWebTestCase
{
    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function year(): int
    {
        return $this->getOrganization()->getCurrentAcademicYear()->getId();
    }

    private function stayId(string $username = 'alfa.alumno7'): int
    {
        return $this->em()->getRepository(StudentProgramWorkcenter::class)->createQueryBuilder('s')
            ->join('s.studentProgram', 'sp')->join('sp.studentEnrollment', 'se')->join('se.person', 'p')
            ->where('p.loginUsername = :username')->setParameter('username', $username)
            ->getQuery()->getSingleResult()->getId();
    }

    private function groupId(): int
    {
        return (int) $this->em()->getConnection()->fetchOne('SELECT id FROM itp_program_group LIMIT 1');
    }

    /**
     * @return list<string> alumnos que aparecen en la página
     */
    private function studentsIn(string $url): array
    {
        $this->client->request('GET', $url);
        self::assertResponseIsSuccessful($url);
        $content = (string) $this->client->getResponse()->getContent();

        return array_values(array_filter(['Alumno 7', 'Alumno 8'], static fn(string $name): bool => str_contains($content, $name)));
    }

    /**
     * @return iterable<string, array{string, list<string>}> usuario y alumnos que ve en los listados
     */
    public static function visibilityProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin', ['Alumno 7', 'Alumno 8']];
        yield 'jefe de departamento' => ['alfa.jefe', ['Alumno 7', 'Alumno 8']];
        yield 'gestor de la FFE' => ['alfa.gestorffe', ['Alumno 7', 'Alumno 8']];
        yield 'tutor docente y de grupo' => ['alfa.tutor', ['Alumno 7', 'Alumno 8']];
        yield 'tutor docente de alumno7' => ['alfa.profesor', ['Alumno 7']];
        yield 'tutor laboral de alumno7' => ['alfa.tutorlaboral', ['Alumno 7']];
        yield 'tutor laboral de alumno8' => ['alfa.tutorlaboral2', ['Alumno 8']];
    }

    /**
     * @dataProvider visibilityProvider
     *
     * @param list<string> $expected
     */
    public function testEachRoleSeesOnlyItsStaysInTheGradingList(string $username, array $expected): void
    {
        $this->login($this->getUser($username));

        self::assertSame($expected, $this->studentsIn('/formacion/valoracion/listar/' . $this->year()), $username);
    }

    /**
     * @dataProvider visibilityProvider
     *
     * @param list<string> $expected
     */
    public function testEachRoleSeesOnlyItsStaysInTheGroupList(string $username, array $expected): void
    {
        if (in_array($username, ['alfa.tutorlaboral', 'alfa.tutorlaboral2'], true)) {
            // la tutoría laboral no participa en la evaluación: el listado del grupo le está cerrado
            $this->login($this->getUser($username));
            $this->client->request('GET', '/formacion/valoracion/evaluacion/grupo/' . $this->groupId());
            self::assertResponseStatusCodeSame(403, $username);

            return;
        }
        $this->login($this->getUser($username));

        self::assertSame($expected, $this->studentsIn('/formacion/valoracion/evaluacion/grupo/' . $this->groupId()), $username);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function evaluationViewerProvider(): iterable
    {
        yield 'administrador local' => ['alfa.admin'];
        yield 'jefe de departamento' => ['alfa.jefe'];
        yield 'gestor de la FFE' => ['alfa.gestorffe'];
        yield 'tutor docente' => ['alfa.profesor'];
        yield 'tutor de grupo' => ['alfa.tutor'];
    }

    /**
     * @dataProvider evaluationViewerProvider
     */
    public function testEvaluationScreensAreOpenToTheTeachingTeam(string $username): void
    {
        $this->login($this->getUser($username));

        $this->client->request('GET', '/formacion/valoracion/evaluacion/' . $this->year());
        self::assertResponseIsSuccessful();
        $this->client->request('GET', '/formacion/valoracion/evaluacion/grupo/detalle/' . $this->stayId());
        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function evaluationOutsiderProvider(): iterable
    {
        yield 'tutor laboral' => ['alfa.tutorlaboral'];
        yield 'alumno de la estancia' => ['alfa.alumno7'];
        yield 'responsable económico' => ['alfa.economico'];
        yield 'coordinador de FP dual' => ['alfa.coordinador'];
    }

    /**
     * @dataProvider evaluationOutsiderProvider
     */
    public function testEvaluationScreensAreClosedToEveryoneElse(string $username): void
    {
        $this->login($this->getUser($username));

        $this->client->request('GET', '/formacion/valoracion/evaluacion/' . $this->year());
        self::assertResponseStatusCodeSame(403);
        // tampoco el listado del alumnado de un grupo, que antes se abría vacío o con su propia estancia
        $this->client->request('GET', '/formacion/valoracion/evaluacion/grupo/' . $this->groupId());
        self::assertResponseStatusCodeSame(403);
        $this->client->request('GET', '/formacion/valoracion/evaluacion/grupo/detalle/' . $this->stayId());
        self::assertResponseStatusCodeSame(403);
    }

    public function testOtherOrganizationCannotListGradings(): void
    {
        $this->login($this->getUser(DemoData::USER_B_LOCAL_ADMIN), true, DemoData::ORG_B_CODE);

        foreach (['/formacion/valoracion/listar/' . $this->year(), '/formacion/valoracion/evaluacion/' . $this->year(),
                     '/formacion/valoracion/evaluacion/grupo/' . $this->groupId()] as $url) {
            $this->client->request('GET', $url);
            self::assertResponseStatusCodeSame(403, $url);
        }
    }

    // --- Calificación de actividades -----------------------------------------------------------------

    public function testTutorGradesAnActivityOfTheStay(): void
    {
        $stay = $this->stayId();
        $this->login($this->getUser('alfa.profesor'));

        $crawler = $this->client->request('GET', "/formacion/valoracion/$stay");
        $form = $crawler->selectButton('Guardar cambios')->form();
        // solo se pueden calificar las actividades habilitadas del formulario (las demás no se envían)
        $editable = array_values(array_filter(
            array_keys($form->all()),
            static fn(string $name): bool => preg_match('/\[activities\]\[\d+\]\[scaleValue\]$/', $name) === 1 && !$form[$name]->isDisabled()
        ));
        self::assertNotEmpty($editable, 'El formulario tiene alguna calificación editable');
        $current = (string) $form[$editable[0]]->getValue();
        $others = array_values(array_filter(
            $form[$editable[0]]->availableOptionValues(),
            static fn(string $value): bool => $value !== '' && $value !== $current
        ));
        self::assertNotEmpty($others);
        $form[$editable[0]]->select($others[0]);

        $this->client->submit($form);

        self::assertResponseRedirects();
        self::assertContains(
            (int) $others[0],
            array_map('intval', $this->em()->getConnection()->fetchFirstColumn(
                'SELECT scale_value_id FROM itp_student_program_workcenter_activity WHERE student_program_workcenter_id = ? AND scale_value_id IS NOT NULL',
                [$stay]
            ))
        );
    }

    public function testDisabledActivitiesAreNotGradedEvenIfTheRequestIncludesThem(): void
    {
        $stay = $this->stayId();
        $this->login($this->getUser('alfa.profesor'));
        $crawler = $this->client->request('GET', "/formacion/valoracion/$stay");
        $form = $crawler->selectButton('Guardar cambios')->form();
        $disabled = array_values(array_filter(
            array_keys($form->all()),
            static fn(string $name): bool => preg_match('/\[activities\]\[\d+\]\[scaleValue\]$/', $name) === 1 && $form[$name]->isDisabled()
        ));
        if ($disabled === []) {
            self::markTestSkipped('Todas las actividades de la estancia son calificables');
        }
        $before = $this->gradedActivities($stay);
        $values = $form->getPhpValues();
        $options = array_values(array_filter($form[$disabled[0]]->availableOptionValues(), static fn(string $v): bool => $v !== ''));
        preg_match('/\[activities\]\[(\d+)\]/', $disabled[0], $m);
        $values['student_program_workcenter_grade']['activities'][(int) $m[1]]['scaleValue'] = $options[0];

        $this->client->request('POST', "/formacion/valoracion/$stay", $values);

        self::assertSame($before, $this->gradedActivities($stay));
    }

    private function gradedActivities(int $stay): int
    {
        return (int) $this->em()->getConnection()->fetchOne(
            'SELECT COUNT(*) FROM itp_student_program_workcenter_activity WHERE student_program_workcenter_id = ? AND scale_value_id IS NOT NULL',
            [$stay]
        );
    }

    public function testGradeFromAnotherScaleIsRejected(): void
    {
        $stay = $this->stayId();
        $this->login($this->getUser('alfa.profesor'));
        $crawler = $this->client->request('GET', "/formacion/valoracion/$stay");
        $values = $crawler->selectButton('Guardar cambios')->form()->getPhpValues();
        // una valoración inexistente o de otro centro no es una opción válida
        $values['student_program_workcenter_grade']['activities'][0]['scaleValue'] = '999999';

        $this->client->request('POST', "/formacion/valoracion/$stay", $values);

        self::assertLessThan(500, $this->client->getResponse()->getStatusCode());
        self::assertSame(
            0,
            (int) $this->em()->getConnection()->fetchOne('SELECT COUNT(*) FROM itp_student_program_workcenter_activity WHERE scale_value_id = 999999')
        );
    }
}
