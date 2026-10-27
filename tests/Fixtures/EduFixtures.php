<?php

namespace App\Tests\Fixtures;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Competency;
use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\Criterion;
use App\Entity\Edu\Department;
use App\Entity\Edu\Grade;
use App\Entity\Edu\Group;
use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\NonWorkingDay;
use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\PerformanceScaleValue;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Subject;
use App\Entity\Edu\Teacher;
use App\Entity\Edu\Teaching;
use App\Entity\Edu\TravelRoute;
use App\Entity\Edu\Training;
use App\Entity\Organization;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Estructura académica de cada organización: departamentos, enseñanza, cursos, grupos, materias,
 * profesorado (con jefe de departamento, director, responsable económico y tutor) y alumnado.
 */
class EduFixtures extends AbstractDemoFixture implements DependentFixtureInterface
{
    public const REF_DEPARTMENT = 'department.';
    public const REF_TRAINING = 'training.';
    public const REF_GRADE_1 = 'grade1.';
    public const REF_GRADE_2 = 'grade2.';
    public const REF_GROUP_1 = 'group1.';
    public const REF_GROUP_2 = 'group2.';
    public const REF_SUBJECT = 'subject.';
    public const REF_TEACHER = 'teacher.';
    public const REF_STUDENT = 'student.';

    public function getDependencies(): array
    {
        return [CoreFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (array_keys(DemoData::ORGANIZATIONS) as $prefix) {
            $this->loadOrganization($manager, $prefix);
        }
        $manager->flush();
    }

    private function loadOrganization(ObjectManager $manager, string $prefix): void
    {
        $organization = $this->getReference(CoreFixtures::REF_ORGANIZATION . $prefix, Organization::class);
        $academicYear = $this->getReference(CoreFixtures::REF_ACADEMIC_YEAR . $prefix, AcademicYear::class);

        // Departamentos
        $informatica = new Department();
        $informatica->setName('Informática')->setInternalCode('INF')->setAcademicYear($academicYear);
        $administracion = new Department();
        $administracion->setName('Administración')->setInternalCode('ADM')->setAcademicYear($academicYear);
        $manager->persist($informatica);
        $manager->persist($administracion);

        // Profesorado
        $teachers = [];
        foreach ([
            DemoData::ROLE_PRINCIPAL => ['Directora', 'Dirección', $administracion],
            DemoData::ROLE_FINANCIAL_MANAGER => ['Secretario', 'Económico', $administracion],
            DemoData::ROLE_DEPARTMENT_HEAD => ['Jefe', 'Informática', $informatica],
            DemoData::ROLE_GROUP_TUTOR => ['Tutor', 'Segundo', $informatica],
            DemoData::ROLE_TEACHER => ['Profesor', 'Docente', $informatica],
        ] as $role => [$firstName, $lastName, $department]) {
            $person = $this->createPerson(
                $manager,
                DemoData::username($prefix, $role),
                $firstName,
                $lastName . ' ' . ucfirst($prefix)
            );
            $teacher = new Teacher();
            $teacher->setPerson($person)->setAcademicYear($academicYear)->setDepartment($department);
            $manager->persist($teacher);
            $teachers[$role] = $teacher;
            $this->addReference(self::REF_TEACHER . $prefix . '.' . $role, $teacher);
        }
        $informatica->setHead($teachers[DemoData::ROLE_DEPARTMENT_HEAD]);
        $academicYear->setPrincipal($teachers[DemoData::ROLE_PRINCIPAL]);
        $academicYear->setFinancialManager($teachers[DemoData::ROLE_FINANCIAL_MANAGER]);

        // Enseñanza, cursos y grupos
        $training = new Training();
        $training
            ->setInternalCode('DAM')
            ->setName('CFGS Desarrollo de Aplicaciones Multiplataforma')
            ->setAcademicYear($academicYear)
            ->setDepartment($informatica);
        $manager->persist($training);

        $competency = new Competency();
        $competency->setTraining($training)->setCode('CG1')->setDescription('Configurar y explotar sistemas informáticos');
        $manager->persist($competency);

        $grade1 = new Grade();
        $grade1->setTraining($training)->setInternalCode('1DAM')->setName('1º DAM');
        $grade2 = new Grade();
        $grade2->setTraining($training)->setInternalCode('2DAM')->setName('2º DAM');
        $manager->persist($grade1);
        $manager->persist($grade2);

        $group1 = new Group();
        $group1->setGrade($grade1)->setInternalCode('1DAM-A')->setName('1º DAM A');
        $group2 = new Group();
        $group2->setGrade($grade2)->setInternalCode('2DAM-A')->setName('2º DAM A')
            ->setTutors(new ArrayCollection([$teachers[DemoData::ROLE_GROUP_TUTOR]]));
        $manager->persist($group1);
        $manager->persist($group2);

        // Materias con sus resultados de aprendizaje y criterios de evaluación
        $subjects = [];
        foreach ([
            ['PRG', 'Programación', $grade1],
            ['BBD', 'Bases de datos', $grade1],
            ['PIN', 'Proyecto intermodular', $grade2],
        ] as [$code, $name, $grade]) {
            $subject = new Subject();
            $subject->setGrade($grade)->setCode($code)->setInternalCode($code)->setName($name);
            $manager->persist($subject);
            $subjects[$code] = $subject;

            $outcome = new LearningOutcome();
            $outcome->setSubject($subject)->setCode('RA1')->setDescription('Resultado de aprendizaje de ' . $name);
            $manager->persist($outcome);
            $criterion = new Criterion();
            $criterion->setLearningOutcome($outcome)->setCode('a')->setName('Criterio a')
                ->setDescription('Criterio de evaluación de ' . $name);
            $manager->persist($criterion);
        }

        // Docencia
        foreach ([
            [$teachers[DemoData::ROLE_TEACHER], $group1, $subjects['PRG']],
            [$teachers[DemoData::ROLE_GROUP_TUTOR], $group1, $subjects['BBD']],
            [$teachers[DemoData::ROLE_GROUP_TUTOR], $group2, $subjects['PIN']],
        ] as [$teacher, $group, $subject]) {
            $teaching = new Teaching();
            $teaching->setTeacher($teacher)->setGroup($group)->setSubject($subject);
            $manager->persist($teaching);
        }

        // Alumnado: tres en segundo curso (el que realiza las prácticas) y uno en primero
        foreach ([
            DemoData::ROLE_STUDENT_1 => $group2,
            DemoData::ROLE_STUDENT_2 => $group2,
            DemoData::ROLE_STUDENT_3 => $group2,
            DemoData::ROLE_STUDENT_4 => $group1,
        ] as $role => $group) {
            $person = $this->createPerson(
                $manager,
                DemoData::username($prefix, $role),
                'Alumno ' . substr($role, -1),
                'Estudiante ' . ucfirst($prefix)
            );
            $enrollment = new StudentEnrollment();
            $enrollment->setPerson($person)->setGroup($group);
            $manager->persist($enrollment);
            $this->addReference(self::REF_STUDENT . $prefix . '.' . $role, $enrollment);
        }

        // Otros datos del curso
        $start = DemoData::currentSchoolYearStart();
        foreach ([['+1 month +10 days', 'Día de la Hispanidad'], ['+3 months +24 days', 'Navidad']] as [$offset, $description]) {
            $day = new NonWorkingDay();
            $day->setAcademicYear($academicYear)
                ->setDate(\DateTime::createFromImmutable($start->modify($offset)))
                ->setDescription($description);
            $manager->persist($day);
        }
        foreach (['Teléfono', 'Correo electrónico'] as $description) {
            $method = new ContactMethod();
            $method->setAcademicYear($academicYear)->setDescription($description)->setEnabled(true);
            $manager->persist($method);
        }

        $scale = new PerformanceScale();
        $scale->setOrganization($organization)->setDescription('Escala de desempeño')->setEnabled(true);
        $manager->persist($scale);
        foreach ([[0, 'Insuficiente', true], [1, 'Suficiente', false], [2, 'Notable', false]] as [$grade, $description, $negative]) {
            $value = new PerformanceScaleValue();
            $value->setPerformanceScale($scale)->setDescription($description)->setNumericGrade($grade)
                ->setNegativeGrade($negative);
            $manager->persist($value);
        }

        foreach ([['Linares - Jaén', 52.5], ['Linares - Úbeda', 28.0]] as [$description, $distance]) {
            $route = new TravelRoute();
            $route->setOrganization($organization)->setDescription($description)->setVerified(true)->setDistance($distance);
            $manager->persist($route);
        }

        $this->addReference(self::REF_DEPARTMENT . $prefix, $informatica);
        $this->addReference(self::REF_TRAINING . $prefix, $training);
        $this->addReference(self::REF_GRADE_1 . $prefix, $grade1);
        $this->addReference(self::REF_GRADE_2 . $prefix, $grade2);
        $this->addReference(self::REF_GROUP_1 . $prefix, $group1);
        $this->addReference(self::REF_GROUP_2 . $prefix, $group2);
        foreach ($subjects as $code => $subject) {
            $this->addReference(self::REF_SUBJECT . $prefix . '.' . $code, $subject);
        }
    }
}
