<?php

namespace App\Tests\Fixtures;

use App\Entity\Company;
use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\Criterion;
use App\Entity\Edu\Department;
use App\Entity\Edu\Grade;
use App\Entity\Edu\Group;
use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\PerformanceScaleValue;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Edu\TravelRoute;
use App\Entity\Person;
use App\Entity\Workcenter;
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\CompanyProgram;
use App\Entity\ItpModule\Contact;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use App\Entity\ItpModule\StudentProgramWorkcenterActivityComment;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\ItpModule\TravelExpense;
use App\Entity\ItpModule\WorkDay;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Módulo de Formación en Empresas (FFE / ITP): programa formativo, enseñanza, grupo con gestor,
 * programas de empresa, alumnado en centros de trabajo, calificación de actividades, jornadas,
 * visitas y gastos de desplazamiento.
 */
class ItpFixtures extends AbstractDemoFixture implements DependentFixtureInterface
{
    public const REF_TRAINING_PROGRAM = 'itp.training-program.';
    public const REF_PROGRAM_GROUP = 'itp.program-group.';
    public const REF_STUDENT_PROGRAM_WORKCENTER_1 = 'itp.spw1.';
    public const REF_STUDENT_PROGRAM_WORKCENTER_2 = 'itp.spw2.';

    public function getDependencies(): array
    {
        return [EduFixtures::class, CompanyFixtures::class, WptFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        foreach (array_keys(DemoData::ORGANIZATIONS) as $prefix) {
            $this->loadOrganization($manager, $prefix);
        }
        $manager->flush();
    }

    private function teacher(string $prefix, string $role): Teacher
    {
        return $this->getReference(EduFixtures::REF_TEACHER . $prefix . '.' . $role, Teacher::class);
    }

    private function loadOrganization(ObjectManager $manager, string $prefix): void
    {
        $academicYear = $this->getReference(CoreFixtures::REF_ACADEMIC_YEAR . $prefix, AcademicYear::class);

        // Gestor de la FFE: un profesor más del departamento de Informática
        $managerPerson = $this->createPerson(
            $manager,
            DemoData::username($prefix, DemoData::ROLE_ITP_MANAGER),
            'Gestor',
            'FFE ' . ucfirst($prefix)
        );
        $itpManager = new Teacher();
        $itpManager
            ->setPerson($managerPerson)
            ->setAcademicYear($academicYear)
            ->setDepartment($this->getReference(EduFixtures::REF_DEPARTMENT . $prefix, Department::class));
        $manager->persist($itpManager);

        // Programa formativo, nivel y grupo
        $program = new TrainingProgram();
        $program
            ->setName('Programa de formación en empresas de DAM')
            ->setTargetHours(10000)
            ->setTotalHours(20000)
            ->setModality(TrainingProgram::MODE_GENERAL)
            ->setPerformanceScale($this->getReference(EduFixtures::REF_PERFORMANCE_SCALE . $prefix, PerformanceScale::class))
            ->setLocked(false);
        $manager->persist($program);

        $programGrade = new ProgramGrade();
        $programGrade
            ->setTrainingProgram($program)
            ->setGrade($this->getReference(EduFixtures::REF_GRADE_2 . $prefix, Grade::class))
            ->setTargetHours(10000);
        $manager->persist($programGrade);

        $programGroup = new ProgramGroup();
        $programGroup
            ->setProgramGrade($programGrade)
            ->setGroup($this->getReference(EduFixtures::REF_GROUP_2 . $prefix, Group::class))
            ->addManager($itpManager)
            ->setNotes('Grupo de FFE')
            ->setLocked(false)
            ->setTargetHours(10000);
        $manager->persist($programGroup);

        // Actividades del programa y programa de la empresa
        $activities = [];
        foreach ([['FFE1', 'Soporte técnico'], ['FFE2', 'Desarrollo de software']] as [$code, $name]) {
            $activity = new Activity();
            $activity
                ->setProgramGrade($programGrade)
                ->setCode($code)
                ->setName($name)
                ->setDescription('Actividad de ' . $name)
                ->addCriterion($this->getReference(EduFixtures::REF_CRITERION . $prefix . '.PIN', Criterion::class));
            $manager->persist($activity);
            $activities[] = $activity;
        }

        $companyProgram = new CompanyProgram();
        $companyProgram
            ->setProgramGrade($programGrade)
            ->setCompany($this->getReference(CompanyFixtures::REF_COMPANY_SOUTH, Company::class))
            ->setAgreementNumber('FFE-001')
            ->setMonitoringInstruments('Cuaderno de seguimiento')
            ->addProgramActivity($activities[0])
            ->addProgramActivity($activities[1]);
        $manager->persist($companyProgram);

        // Alumnado en centros de trabajo
        [$start, $end] = DemoData::trainingPeriod();
        $workTutor1 = $this->getReference(WptFixtures::REF_WORK_TUTOR_1 . $prefix, Person::class);
        $workTutor2 = $this->getReference(WptFixtures::REF_WORK_TUTOR_2 . $prefix, Person::class);
        $spw1 = $this->studentAtWorkcenter(
            $manager,
            $programGroup,
            $prefix,
            DemoData::ROLE_STUDENT_7,
            CompanyFixtures::REF_WORKCENTER_MAIN,
            $this->teacher($prefix, DemoData::ROLE_TEACHER),
            $workTutor1,
            $start,
            $end
        );
        $spw2 = $this->studentAtWorkcenter(
            $manager,
            $programGroup,
            $prefix,
            DemoData::ROLE_STUDENT_8,
            CompanyFixtures::REF_WORKCENTER_UBEDA,
            $this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR),
            $workTutor2,
            $start,
            $end
        );

        // Valoración de actividades, un comentario y jornadas
        $scaleValue = $this->getReference(EduFixtures::REF_PERFORMANCE_SCALE_VALUE . $prefix . '.2', PerformanceScaleValue::class);
        $valuedBy = $this->teacher($prefix, DemoData::ROLE_TEACHER)->getPerson();
        $today = DemoData::today();
        foreach ([$spw1, $spw2] as $spw) {
            foreach ($activities as $index => $activity) {
                $spwActivity = new StudentProgramWorkcenterActivity();
                $spwActivity
                    ->setActivity($activity)
                    ->setStudentProgramWorkcenter($spw)
                    ->setDisabled(false);
                if ($index === 0) {
                    $spwActivity->setScaleValue($scaleValue)->setValuedBy($valuedBy)->setDetails('Actividad completada');
                }
                $manager->persist($spwActivity);

                if ($index === 0) {
                    $comment = new StudentProgramWorkcenterActivityComment();
                    $comment
                        ->setStudentProgramActivity($spwActivity)
                        ->setPerson($valuedBy)
                        ->setComment('Buen desempeño en la actividad')
                        ->setTimestamp($today);
                    $manager->persist($comment);
                }
            }

            for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
                if ((int) $date->format('N') >= 6) {
                    continue;
                }
                $workDay = new WorkDay();
                $workDay
                    ->setStudentProgramWorkcenter($spw)
                    ->setDate($date)
                    ->setHours(500) // las horas se guardan en centésimas: 500 son 5 horas
                    ->setLocked(false)
                    ->setAbsence(WorkDay::ABSENCE_NONE)
                    ->addActivity($activities[0]);
                if ($date <= $today) {
                    $workDay->setNotes('Jornada de formación en la empresa');
                }
                $manager->persist($workDay);
            }
        }

        // Visita al centro de trabajo y gasto de desplazamiento
        $visitDate = max($start, $today->modify('-12 days'))->setTime(11, 0);
        $contact = new Contact();
        $contact
            ->setDateTime(\DateTime::createFromImmutable($visitDate))
            ->setTeacher($this->teacher($prefix, DemoData::ROLE_TEACHER))
            ->setWorkcenter($this->getReference(CompanyFixtures::REF_WORKCENTER_MAIN, Workcenter::class))
            ->setMethod($this->getReference(EduFixtures::REF_CONTACT_METHOD . $prefix, ContactMethod::class))
            ->setTrainingPrograms(new ArrayCollection([$program]))
            ->setStudentEnrollments(new ArrayCollection([
                $this->getReference(EduFixtures::REF_STUDENT . $prefix . '.' . DemoData::ROLE_STUDENT_7, StudentEnrollment::class),
            ]))
            ->setDetail('<p>Visita de seguimiento de la <b>FFE</b>.</p>');
        $manager->persist($contact);

        $expense = new TravelExpense();
        $expense
            ->setFromDateTime(\DateTime::createFromImmutable($visitDate->modify('-30 minutes')))
            ->setToDateTime(\DateTime::createFromImmutable($visitDate->modify('+2 hours')))
            ->setTeacher($this->teacher($prefix, DemoData::ROLE_TEACHER))
            ->setTravelRoute($this->getReference(EduFixtures::REF_TRAVEL_ROUTE . $prefix, TravelRoute::class))
            ->setTrainingPrograms(new ArrayCollection([$program]))
            ->setDescription('Visita FFE')
            ->setOtherExpenses(0);
        $manager->persist($expense);

        $this->addReference(self::REF_TRAINING_PROGRAM . $prefix, $program);
        $this->addReference(self::REF_PROGRAM_GROUP . $prefix, $programGroup);
        $this->addReference(self::REF_STUDENT_PROGRAM_WORKCENTER_1 . $prefix, $spw1);
        $this->addReference(self::REF_STUDENT_PROGRAM_WORKCENTER_2 . $prefix, $spw2);
    }

    private function studentAtWorkcenter(
        ObjectManager $manager,
        ProgramGroup $programGroup,
        string $prefix,
        string $studentRole,
        string $workcenterReference,
        Teacher $educationalTutor,
        Person $workTutor,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): StudentProgramWorkcenter {
        $studentProgram = new StudentProgram();
        $studentProgram
            ->setProgramGroup($programGroup)
            ->setStudentEnrollment(
                $this->getReference(EduFixtures::REF_STUDENT . $prefix . '.' . $studentRole, StudentEnrollment::class)
            )
            ->setAuthorizationNeeded(false)
            ->setAdaptationNeeded(false);
        $manager->persist($studentProgram);

        $spw = new StudentProgramWorkcenter();
        $spw
            ->setStudentProgram($studentProgram)
            ->setWorkcenter($this->getReference($workcenterReference, Workcenter::class))
            ->setEducationalTutor($educationalTutor)
            ->setWorkTutor($workTutor)
            ->setStartDate($start)
            ->setEndDate($end);
        $manager->persist($spw);

        return $spw;
    }
}
