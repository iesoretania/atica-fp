<?php

namespace App\Tests\Functional;

use App\Entity\Edu\Criterion;
use App\Entity\Edu\Department;
use App\Entity\Edu\Grade;
use App\Entity\Edu\Group;
use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Subject;
use App\Entity\Edu\Teaching;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\Person;
use App\Tests\Fixtures\DemoData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Importaciones con ficheros válidos de Séneca: se comprueban los datos que se crean o actualizan, que reimportar el
 * mismo fichero no duplica nada y que las filas que no corresponden (bajas, grupos desconocidos) se ignoran. Las
 * importaciones se encadenan como en un centro real: profesorado, departamentos, grupos, materias y alumnado.
 */
class ImportValidDataTest extends AppWebTestCase
{
    private const COURSE = '1º F.P.I.G.S. (Desarrollo de Aplicaciones Web)';

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();

        return $em;
    }

    private function csv(array $header, array ...$rows): string
    {
        $quote = static fn(string $cell): string => '"' . $cell . '"';
        $lines = [implode(',', array_map($quote, $header))];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map($quote, $row));
        }

        // los ficheros de Séneca van codificados en ISO-8859-1
        return mb_convert_encoding(implode("\n", $lines) . "\n", 'ISO-8859-1', 'UTF-8');
    }

    /**
     * Sube el fichero a la importación indicada con las opciones del formulario (por el final del nombre del campo)
     *
     * @param array<string, string> $options
     */
    private function import(string $route, string $content, array $options = [], array $routeParameters = []): Crawler
    {
        $url = static::getContainer()->get('router')->generate($route, $routeParameters);
        $crawler = $this->client->request('GET', $url);
        self::assertResponseIsSuccessful($url);

        $path = tempnam(sys_get_temp_dir(), 'import') . '.csv';
        file_put_contents($path, $content);
        try {
            $form = $crawler->filter('input[type=file]')->closest('form')->form();
            foreach (array_keys($form->all()) as $name) {
                if (str_ends_with($name, '[file]')) {
                    $form[$name]->upload($path);
                }
                foreach ($options as $suffix => $value) {
                    if (str_ends_with($name, "[$suffix]")) {
                        $form[$name]->select($value);
                    }
                }
            }
            $result = $this->client->submit($form);
            self::assertResponseIsSuccessful();

            return $result;
        } finally {
            @unlink($path);
        }
    }

    /**
     * Números que muestra el informe de la importación junto a las etiquetas indicadas
     *
     * @return array{int, int}
     */
    private function reportedTotals(Crawler $crawler, string $newLabel, string $oldLabel): array
    {
        $text = preg_replace('/\s+/', ' ', strip_tags($crawler->filter('body')->html()));
        preg_match('/' . preg_quote($newLabel, '/') . '\D*(\d+)/u', $text, $new);
        preg_match('/' . preg_quote($oldLabel, '/') . '\D*(\d+)/u', $text, $old);

        return [(int) ($new[1] ?? -1), (int) ($old[1] ?? -1)];
    }

    private function admin(): void
    {
        $this->login($this->getUser(DemoData::USER_A_LOCAL_ADMIN));
    }

    private function person(string $internalCode): ?Person
    {
        return $this->em()->getRepository(Person::class)->findOneBy(['internalCode' => $internalCode]);
    }

    private function importTeachers(): void
    {
        $this->import('organization_import_teacher_form', $this->csv(
            ['Usuario IdEA', 'DNI/Pasaporte', 'Empleado/a'],
            ['ana.garcia', '11111111A', 'García López, Ana'],
            ['juan.ruiz', '22222222B', 'Ruiz Pérez, Juan']
        ));
    }

    private function importGroup(string $tutor = 'García López, Ana (Tutor)'): void
    {
        $this->import('organization_import_group_form', $this->csv(
            ['Unidad', 'Curso', 'Tutor/a'],
            ['1ºA', self::COURSE, $tutor]
        ), ['extractTutors' => '1']);
    }

    private function group(string $internalCode = '1ºA'): ?Group
    {
        return $this->em()->getRepository(Group::class)->findOneBy(['internalCode' => $internalCode]);
    }

    // --- Departamentos --------------------------------------------------------------------------------

    public function testDepartmentsAreCreatedWithTheirHeadAndNotDuplicated(): void
    {
        $this->admin();
        $this->importTeachers();
        $file = $this->csv(
            ['Descripción', 'Jefe de departamento'],
            ['Matemáticas', 'García López, Ana'],
            ['Física y Química', 'Nadie Conocido, Pepe']
        );

        $this->import('organization_import_department_form', $file, ['extractHeads' => '1']);

        $repository = $this->em()->getRepository(Department::class);
        $mathematics = $repository->findOneBy(['internalCode' => 'Matemáticas']);
        self::assertNotNull($mathematics);
        self::assertSame('García López, Ana', $mathematics->getHead()?->getPerson()->getInternalCode());
        $physics = $repository->findOneBy(['internalCode' => 'Física y Química']);
        self::assertNotNull($physics);
        self::assertNull($physics->getHead(), 'Una jefatura desconocida se ignora');

        $count = $repository->count([]);
        $this->import('organization_import_department_form', $file, ['extractHeads' => '1']);
        self::assertSame($count, $this->em()->getRepository(Department::class)->count([]), 'Reimportar no duplica');
    }

    public function testDepartmentsAreImportedWithoutHeadsWhenNotRequested(): void
    {
        $this->admin();
        $this->importTeachers();

        $this->import('organization_import_department_form', $this->csv(
            ['Descripción', 'Jefe de departamento'],
            ['Latín', 'García López, Ana']
        ), ['extractHeads' => '0']);

        $department = $this->em()->getRepository(Department::class)->findOneBy(['internalCode' => 'Latín']);
        self::assertNotNull($department);
        self::assertNull($department->getHead());
    }

    // --- Grupos --------------------------------------------------------------------------------------

    public function testGroupsCreateTheirTrainingAndGradeAndAssignTutors(): void
    {
        $this->admin();
        $this->importTeachers();

        $this->importGroup();

        $group = $this->group();
        self::assertNotNull($group);
        self::assertSame('1º Desarrollo de Aplicaciones Web', $group->getGrade()->getInternalCode());
        self::assertSame('F.P.I.G.S. Desarrollo de Aplicaciones Web', $group->getGrade()->getTraining()->getInternalCode());
        self::assertSame(['García López, Ana'], array_map(
            static fn($teacher): string => $teacher->getPerson()->getInternalCode(),
            $group->getTutors()->toArray()
        ));
    }

    public function testReimportingGroupsDoesNotDuplicateAnything(): void
    {
        $this->admin();
        $this->importTeachers();
        $this->importGroup();
        $em = $this->em();
        $counts = [$em->getRepository(Group::class)->count([]), $em->getRepository(Grade::class)->count([]), $em->getRepository(Training::class)->count([])];

        $this->importGroup();

        $em = $this->em();
        self::assertSame($counts, [$em->getRepository(Group::class)->count([]), $em->getRepository(Grade::class)->count([]), $em->getRepository(Training::class)->count([])]);
    }

    public function testRestrictedImportOnlyCreatesVocationalTrainingGroups(): void
    {
        $this->admin();

        $this->import('organization_import_group_form', $this->csv(
            ['Unidad', 'Curso', 'Tutor/a'],
            ['2ºB', '2º de Bachillerato (Ciencias)', ''],
            ['1ºC', self::COURSE, '']
        ), ['restricted' => '1']);

        self::assertNull($this->group('2ºB'), 'El bachillerato no es de FP');
        self::assertNotNull($this->group('1ºC'));
    }

    // --- Materias ------------------------------------------------------------------------------------

    public function testSubjectsAreCreatedWithTheirTeachingAndNotDuplicated(): void
    {
        $this->admin();
        $this->importTeachers();
        $this->importGroup();
        $file = $this->csv(
            ['Materia', 'Unidad', 'Curso', 'Profesor/a'],
            ['Programación', '1ºA', self::COURSE, 'García López, Ana'],
            ['Programación', '1ºA', self::COURSE, 'Ruiz Pérez, Juan'],
            ['Bases de datos', '1ºA', self::COURSE, 'Ruiz Pérez, Juan'],
            ['Materia de grupo desconocido', '9ºZ', self::COURSE, 'Ruiz Pérez, Juan']
        );

        $crawler = $this->import('organization_import_subject_form', $file, ['extractTeachers' => '1']);
        self::assertSame([2, 0], $this->reportedTotals($crawler, 'Materias nuevas incorporados', 'Materias ya existentes'));

        $em = $this->em();
        $subjects = $em->getRepository(Subject::class)->findBy(['internalCode' => 'Programación']);
        self::assertCount(1, $subjects, 'Una materia con varios docentes se crea una sola vez');
        self::assertNotNull($em->getRepository(Subject::class)->findOneBy(['internalCode' => 'Bases de datos']));
        self::assertNull($em->getRepository(Subject::class)->findOneBy(['internalCode' => 'Materia de grupo desconocido']));
        self::assertSame(3, $em->getRepository(Teaching::class)->count(['group' => $this->group()]));

        $count = $em->getRepository(Subject::class)->count([]);
        $teachings = $em->getRepository(Teaching::class)->count([]);
        $crawler = $this->import('organization_import_subject_form', $file, ['extractTeachers' => '1']);
        // las dos filas de "Programación" son la misma materia: el informe cuenta 2 materias, no 3
        self::assertSame([0, 2], $this->reportedTotals($crawler, 'Materias nuevas incorporados', 'Materias ya existentes'));
        $em = $this->em();
        self::assertSame($count, $em->getRepository(Subject::class)->count([]), 'Reimportar no duplica materias');
        self::assertSame($teachings, $em->getRepository(Teaching::class)->count([]), 'Reimportar no duplica la docencia');
    }

    public function testSubjectsAreImportedWithoutTeachingWhenNotRequested(): void
    {
        $this->admin();
        $this->importTeachers();
        $this->importGroup();

        $this->import('organization_import_subject_form', $this->csv(
            ['Materia', 'Unidad', 'Curso', 'Profesor/a'],
            ['Redes', '1ºA', self::COURSE, 'Ruiz Pérez, Juan']
        ), ['extractTeachers' => '0']);

        $em = $this->em();
        self::assertNotNull($em->getRepository(Subject::class)->findOneBy(['internalCode' => 'Redes']));
        self::assertSame(0, $em->getRepository(Teaching::class)->count(['group' => $this->group()]));
    }

    // --- Alumnado ------------------------------------------------------------------------------------

    private function studentFile(): string
    {
        return $this->csv(
            ['Alumno/a', 'Estado Matrícula', 'Unidad', 'Nº Id. Escolar', 'DNI/Pasaporte', 'Nombre', 'Primer apellido', 'Segundo apellido', 'Sexo'],
            ['Pérez Gómez, Luis', '', '1ºA', '1000001', '33333333C', 'Luis', 'Pérez', 'Gómez', 'H'],
            ['Martín Sáez, Marta', '', '1ºA', '1000002', '44444444D', 'Marta', 'Martín', 'Sáez', 'M'],
            ['Baja Anulada, Pedro', 'Anulada', '1ºA', '1000003', '55555555E', 'Pedro', 'Baja', 'Anulada', 'H'],
            ['De Otro Grupo, Eva', '', '9ºZ', '1000004', '66666666F', 'Eva', 'De Otro', 'Grupo', 'M']
        );
    }

    public function testStudentsAreCreatedWithTheirEnrollmentAndForcedPasswordChange(): void
    {
        $this->admin();
        $this->importTeachers();
        $this->importGroup();

        $this->import('organization_import_student_form', $this->studentFile());

        $em = $this->em();
        $luis = $em->getRepository(Person::class)->findOneBy(['uniqueIdentifier' => '1000001']);
        self::assertNotNull($luis);
        self::assertSame('Luis', $luis->getFirstName());
        self::assertSame('Pérez Gómez', $luis->getLastName());
        self::assertSame(Person::GENDER_MALE, $luis->getGender());
        self::assertSame(Person::GENDER_FEMALE, $em->getRepository(Person::class)->findOneBy(['uniqueIdentifier' => '1000002'])->getGender());
        // el acceso inicial se identifica con el número escolar y obliga a cambiar la contraseña
        self::assertSame('1000001', $luis->getLoginUsername());
        self::assertTrue($luis->isForcePasswordChange());
        self::assertNotSame(DemoData::ORG_A_CODE, $luis->getPassword(), 'La contraseña se guarda cifrada');
        self::assertCount(1, $em->getRepository(StudentEnrollment::class)->findBy(['person' => $luis]));
        self::assertNull($em->getRepository(Person::class)->findOneBy(['uniqueIdentifier' => '1000003']), 'Las matrículas anuladas se ignoran');
        self::assertNull($em->getRepository(Person::class)->findOneBy(['uniqueIdentifier' => '1000004']), 'Los grupos desconocidos se ignoran');
    }

    public function testReimportingStudentsDoesNotDuplicatePeopleOrEnrollments(): void
    {
        $this->admin();
        $this->importTeachers();
        $this->importGroup();
        $this->import('organization_import_student_form', $this->studentFile());
        $em = $this->em();
        $people = $em->getRepository(Person::class)->count([]);
        $enrollments = $em->getRepository(StudentEnrollment::class)->count([]);

        $this->import('organization_import_student_form', $this->studentFile());

        $em = $this->em();
        self::assertSame($people, $em->getRepository(Person::class)->count([]));
        self::assertSame($enrollments, $em->getRepository(StudentEnrollment::class)->count([]));
    }

    // --- Usuarios PASEN --------------------------------------------------------------------------------

    private function importStudents(): void
    {
        $this->importTeachers();
        $this->importGroup();
        $this->import('organization_import_student_form', $this->studentFile());
    }

    public function testPasenUsernamesAreAssignedByName(): void
    {
        $this->admin();
        $this->importStudents();

        $this->import('organization_import_student_login_form', $this->csv(
            ['Nombre', 'Usuario IdEA', 'Activo en Séneca'],
            ['Pérez Gómez, Luis', 'lperez001', 'Sí'],
            ['Martín Sáez, Marta', 'mmartin002', 'No'],
            ['Desconocido Total, Ana', 'adesconocida', 'Sí']
        ));

        $em = $this->em();
        $luis = $em->getRepository(Person::class)->findOneBy(['uniqueIdentifier' => '1000001']);
        self::assertSame('lperez001', $luis->getLoginUsername());
        self::assertTrue($luis->getExternalCheck());
        $marta = $em->getRepository(Person::class)->findOneBy(['uniqueIdentifier' => '1000002']);
        self::assertSame('1000002', $marta->getLoginUsername(), 'El alumnado inactivo en Séneca no se actualiza');
        self::assertNull($em->getRepository(Person::class)->findOneBy(['loginUsername' => 'adesconocida']));
    }

    public function testPasenUsernameInUseByAnotherPersonIsNotAssigned(): void
    {
        $this->admin();
        $this->importStudents();

        // el usuario ya lo tiene el profesorado de las fixtures
        $this->import('organization_import_student_login_form', $this->csv(
            ['Nombre', 'Usuario IdEA', 'Activo en Séneca'],
            ['Pérez Gómez, Luis', DemoData::USER_A_TEACHER, 'Sí']
        ));

        $em = $this->em();
        self::assertSame('1000001', $em->getRepository(Person::class)->findOneBy(['uniqueIdentifier' => '1000001'])->getLoginUsername());
        self::assertSame(DemoData::USER_A_TEACHER, $em->getRepository(Person::class)->findOneBy(['loginUsername' => DemoData::USER_A_TEACHER])->getLoginUsername());
    }

    // --- Criterios de evaluación ---------------------------------------------------------------------

    public function testCriteriaCreateSubjectsLearningOutcomesAndCriteriaWithoutDuplicates(): void
    {
        $this->admin();
        $this->importGroup();
        $grade = $this->group()->getGrade()->getId();
        $file = $this->csv(
            ['Módulo Profesional', 'Resultado de Aprendizaje', 'Criterios de Evaluación'],
            ['Programación', '1. Reconoce la estructura de un programa', 'a) Se han identificado los bloques'],
            ['Programación', '1. Reconoce la estructura de un programa', 'b) Se han creado proyectos'],
            ['Programación', '2. Escribe código', 'a) Se han escrito programas']
        );

        $this->import('organization_import_criteria_form', $file, ['grade' => (string) $grade]);

        $em = $this->em();
        $subject = $em->getRepository(Subject::class)->findOneBy(['name' => 'Programación', 'grade' => $grade]);
        self::assertNotNull($subject);
        $outcomes = $em->getRepository(LearningOutcome::class)->findBy(['subject' => $subject], ['code' => 'ASC']);
        self::assertSame(['RA1', 'RA2'], array_map(static fn(LearningOutcome $o): string => $o->getCode(), $outcomes));
        self::assertSame('Reconoce la estructura de un programa', $outcomes[0]->getDescription());
        self::assertSame(2, $em->getRepository(Criterion::class)->count(['learningOutcome' => $outcomes[0]]));
        self::assertSame(1, $em->getRepository(Criterion::class)->count(['learningOutcome' => $outcomes[1]]));

        $criteria = $em->getRepository(Criterion::class)->count([]);
        $this->import('organization_import_criteria_form', $file, ['grade' => (string) $grade]);
        self::assertSame($criteria, $this->em()->getRepository(Criterion::class)->count([]), 'Reimportar actualiza, no duplica');
    }

    public function testCriteriaImportUpdatesExistingText(): void
    {
        $this->admin();
        $this->importGroup();
        $grade = $this->group()->getGrade()->getId();
        $header = ['Módulo Profesional', 'Resultado de Aprendizaje', 'Criterios de Evaluación'];
        $this->import('organization_import_criteria_form', $this->csv($header, ['Redes', '1. Texto antiguo', 'a) Criterio antiguo']), ['grade' => (string) $grade]);

        $this->import('organization_import_criteria_form', $this->csv($header, ['Redes', '1. Texto nuevo', 'a) Criterio nuevo']), ['grade' => (string) $grade]);

        $em = $this->em();
        $subject = $em->getRepository(Subject::class)->findOneBy(['name' => 'Redes', 'grade' => $grade]);
        $outcome = $em->getRepository(LearningOutcome::class)->findOneBy(['subject' => $subject]);
        self::assertSame('Texto nuevo', $outcome->getDescription());
        self::assertSame('Criterio nuevo', $em->getRepository(Criterion::class)->findOneBy(['learningOutcome' => $outcome])->getName());
    }

    // --- Actividades de la FFE -------------------------------------------------------------------------

    private function programGrade(): ProgramGrade
    {
        return $this->em()->getRepository(ProgramGrade::class)->createQueryBuilder('pg')
            ->join('pg.grade', 'g')->join('g.training', 't')->join('t.academicYear', 'ay')
            ->where('ay.organization = :organization')->setParameter('organization', $this->getOrganization())
            ->setMaxResults(1)->getQuery()->getSingleResult();
    }

    public function testItpActivitiesAreLinkedToTheCriteriaOfTheGrade(): void
    {
        $this->admin();
        $programGrade = $this->programGrade();
        $subject = $this->em()->getRepository(Subject::class)->findOneBy(['grade' => $programGrade->getGrade()]);
        $criterion = $this->em()->getRepository(Criterion::class)->createQueryBuilder('c')
            ->join('c.learningOutcome', 'lo')->where('lo.subject = :subject')->setParameter('subject', $subject)
            ->setMaxResults(1)->getQuery()->getSingleResult();
        $outcomeNumber = preg_replace('/^RA/', '', $criterion->getLearningOutcome()->getCode());
        $file = $this->csv(
            ['Actividad', 'Módulo', 'Resultado de aprendizaje asociado', 'Criterio de evaluación'],
            ['AF90. Actividad importada', 'ABC (Módulo) / ' . $subject->getName(), "R.A.$outcomeNumber. Texto", $criterion->getCode() . '. Texto'],
            ['AF91. Con módulo desconocido', 'XYZ (Otro) / Módulo que no existe', "R.A.$outcomeNumber. Texto", $criterion->getCode() . '. Texto']
        );

        $this->import('in_company_training_phase_activity_import_form', $file, [], ['programGrade' => $programGrade->getId()]);

        $em = $this->em();
        $activity = $em->getRepository(Activity::class)->findOneBy(['programGrade' => $programGrade->getId(), 'code' => 'AF90']);
        self::assertNotNull($activity);
        self::assertSame('Actividad importada', $activity->getName());
        self::assertSame([$criterion->getId()], array_map(static fn(Criterion $c): int => $c->getId(), $activity->getCriteria()->toArray()));
        $unknown = $em->getRepository(Activity::class)->findOneBy(['programGrade' => $programGrade->getId(), 'code' => 'AF91']);
        self::assertSame(0, $unknown?->getCriteria()->count() ?? 0, 'Un módulo desconocido no vincula criterios');

        $count = $em->getRepository(Activity::class)->count(['programGrade' => $programGrade->getId()]);
        $this->import('in_company_training_phase_activity_import_form', $file, [], ['programGrade' => $programGrade->getId()]);
        self::assertSame($count, $this->em()->getRepository(Activity::class)->count(['programGrade' => $programGrade->getId()]));
    }

    public function testItpActivityWithAWrongFormatIsReportedWithoutSavingAnything(): void
    {
        $this->admin();
        $programGrade = $this->programGrade();
        $before = $this->em()->getRepository(Activity::class)->count(['programGrade' => $programGrade->getId()]);

        $this->import('in_company_training_phase_activity_import_form', $this->csv(
            ['Actividad', 'Módulo', 'Resultado de aprendizaje asociado', 'Criterio de evaluación'],
            ['Actividad sin código', 'ABC (Módulo) / Algo', 'R.A.1. Texto', 'a. Texto']
        ), [], ['programGrade' => $programGrade->getId()]);

        self::assertSame($before, $this->em()->getRepository(Activity::class)->count(['programGrade' => $programGrade->getId()]));
    }
}
