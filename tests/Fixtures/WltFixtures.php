<?php

namespace App\Tests\Fixtures;

use App\Entity\Company;
use App\Entity\Edu\Competency;
use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\Department;
use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\PerformanceScaleValue;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Group;
use App\Entity\Edu\Teacher;
use App\Entity\Edu\TravelRoute;
use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Workcenter;
use App\Entity\WltModule\Activity;
use App\Entity\WltModule\ActivityRealization;
use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\AgreementActivityRealization;
use App\Entity\WltModule\AgreementActivityRealizationComment;
use App\Entity\WltModule\Contact;
use App\Entity\WltModule\LearningProgram;
use App\Entity\WltModule\Meeting;
use App\Entity\WltModule\Project;
use App\Entity\WltModule\TravelExpense;
use App\Entity\WltModule\WorkDay;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Módulo de FP dual (WLT): proyecto, actividades y realizaciones, programa formativo de la empresa,
 * convenios, calificaciones, jornadas, reuniones, visitas y gastos de desplazamiento.
 */
class WltFixtures extends AbstractDemoFixture implements DependentFixtureInterface
{
    public const REF_PROJECT = 'wlt.project.';
    public const REF_AGREEMENT_1 = 'wlt.agreement1.';
    public const REF_AGREEMENT_2 = 'wlt.agreement2.';

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

    private function student(string $prefix, string $role): StudentEnrollment
    {
        return $this->getReference(EduFixtures::REF_STUDENT . $prefix . '.' . $role, StudentEnrollment::class);
    }

    private function loadOrganization(ObjectManager $manager, string $prefix): void
    {
        $organization = $this->getReference(CoreFixtures::REF_ORGANIZATION . $prefix, Organization::class);
        $academicYear = $this->getReference(CoreFixtures::REF_ACADEMIC_YEAR . $prefix, AcademicYear::class);

        // Coordinador del proyecto: un profesor más del departamento de Informática
        $coordinator = $this->createPerson(
            $manager,
            DemoData::username($prefix, DemoData::ROLE_PROJECT_MANAGER),
            'Coordinador',
            'Dual ' . ucfirst($prefix)
        );
        $coordinatorTeacher = new Teacher();
        $coordinatorTeacher
            ->setPerson($coordinator)
            ->setAcademicYear($academicYear)
            ->setDepartment($this->getReference(EduFixtures::REF_DEPARTMENT . $prefix, Department::class));
        $manager->persist($coordinatorTeacher);

        $group = $this->getReference(EduFixtures::REF_GROUP_2 . $prefix, Group::class);
        $student5 = $this->student($prefix, DemoData::ROLE_STUDENT_5);
        $student6 = $this->student($prefix, DemoData::ROLE_STUDENT_6);

        // Proyecto
        $project = new Project();
        $project
            ->setName('Proyecto de FP dual en Desarrollo de Aplicaciones')
            ->setOrganization($organization)
            ->setManager($coordinator)
            ->setGroups(new ArrayCollection([$group]))
            ->setStudentEnrollments(new ArrayCollection([$student5, $student6]))
            ->setPerformanceScale($this->getReference(EduFixtures::REF_PERFORMANCE_SCALE . $prefix, PerformanceScale::class))
            ->setLocked(false);
        $manager->persist($project);

        // Actividades y realizaciones (con sus resultados de aprendizaje y competencias)
        $outcome = $this->getReference(EduFixtures::REF_LEARNING_OUTCOME . $prefix . '.PIN', LearningOutcome::class);
        $competency = $this->getReference(EduFixtures::REF_COMPETENCY . $prefix, Competency::class);
        $realizations = [];
        foreach ([['A1', 'Desarrollo de un portal web'], ['A2', 'Mantenimiento de bases de datos']] as [$code, $description]) {
            $activity = new Activity();
            $activity
                ->setProject($project)
                ->setCode($code)
                ->setDescription($description)
                ->setPriorLearning('Conocimientos básicos de programación')
                ->setCompetencies(new ArrayCollection([$competency]));
            $manager->persist($activity);

            foreach ([1, 2] as $number) {
                $realization = new ActivityRealization();
                $realization
                    ->setActivity($activity)
                    ->setCode($code . '.' . $number)
                    ->setDescription('Tarea ' . $number . ' de ' . $description)
                    ->setLearningOutcomes(new ArrayCollection([$outcome]));
                $manager->persist($realization);
                $realizations[] = $realization;
            }
        }

        // Programa formativo de la empresa
        $program = new LearningProgram();
        $program
            ->setCompany($this->getReference(CompanyFixtures::REF_COMPANY_SOUTH, Company::class))
            ->setProject($project)
            ->setActivityRealizations(new ArrayCollection($realizations));
        $manager->persist($program);

        // Convenios
        [$start, $end] = DemoData::trainingPeriod();
        $agreement1 = $this->agreement(
            $manager,
            $project,
            CompanyFixtures::REF_WORKCENTER_MAIN,
            $student5,
            $this->getReference(WptFixtures::REF_WORK_TUTOR_1 . $prefix, Person::class),
            $this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR),
            'DUAL-1',
            $start,
            $end
        );
        $agreement2 = $this->agreement(
            $manager,
            $project,
            CompanyFixtures::REF_WORKCENTER_UBEDA,
            $student6,
            $this->getReference(WptFixtures::REF_WORK_TUTOR_2 . $prefix, Person::class),
            $this->teacher($prefix, DemoData::ROLE_TEACHER),
            'DUAL-2',
            $start,
            $end
        );

        // Calificaciones de las realizaciones y un comentario
        $grade = $this->getReference(EduFixtures::REF_PERFORMANCE_SCALE_VALUE . $prefix . '.2', PerformanceScaleValue::class);
        $gradedBy = $this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR)->getPerson();
        $today = new \DateTimeImmutable('today');
        foreach ([$agreement1, $agreement2] as $agreement) {
            foreach ($realizations as $index => $realization) {
                $agreementRealization = new AgreementActivityRealization();
                $agreementRealization
                    ->setAgreement($agreement)
                    ->setActivityRealization($realization)
                    ->setDisabled(false);
                if ($index % 2 === 0) {
                    $agreementRealization->setGrade($grade)->setGradedBy($gradedBy)->setGradedOn(\DateTime::createFromImmutable($today));
                }
                $manager->persist($agreementRealization);

                if ($index === 0) {
                    $comment = new AgreementActivityRealizationComment();
                    $comment
                        ->setAgreementActivityRealization($agreementRealization)
                        ->setPerson($gradedBy)
                        ->setComment('Realización correcta de la tarea')
                        ->setTimestamp(\DateTime::createFromImmutable($today));
                    $manager->persist($comment);
                }
            }
        }

        // Jornadas de los días laborables del período
        foreach ([$agreement1, $agreement2] as $agreement) {
            for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
                if ((int) $date->format('N') >= 6) {
                    continue;
                }
                $workDay = new WorkDay();
                $workDay
                    ->setAgreement($agreement)
                    ->setDate(\DateTime::createFromImmutable($date))
                    ->setHours(6)
                    ->setLocked(false)
                    ->setAbsence(WorkDay::NO_ABSENCE)
                    ->setActivityRealizations(new ArrayCollection([$realizations[0], $realizations[1]]));
                if ($date <= $today) {
                    $workDay->setNotes('Jornada de formación dual');
                }
                $manager->persist($workDay);
            }
        }

        // Reunión de seguimiento, visita al centro de trabajo y gasto de desplazamiento
        $meetingDate = max($start, $today->modify('-3 weeks'))->setTime(12, 0);
        $meeting = new Meeting();
        $meeting
            ->setProject($project)
            ->setDateTime(\DateTime::createFromImmutable($meetingDate))
            ->setCreatedBy($this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR))
            ->setTeachers(new ArrayCollection([$this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR)]))
            ->setStudentEnrollments(new ArrayCollection([$student5, $student6]))
            ->setDetail('<p>Reunión de <b>seguimiento</b> del proyecto.</p>');
        $manager->persist($meeting);

        $visitDate = max($start, $today->modify('-10 days'))->setTime(10, 0);
        $contact = new Contact();
        $contact
            ->setDateTime(\DateTime::createFromImmutable($visitDate))
            ->setTeacher($this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR))
            ->setWorkcenter($this->getReference(CompanyFixtures::REF_WORKCENTER_MAIN, Workcenter::class))
            ->setMethod($this->getReference(EduFixtures::REF_CONTACT_METHOD . $prefix, ContactMethod::class))
            ->setProjects(new ArrayCollection([$project]))
            ->setStudentEnrollments(new ArrayCollection([$student5]))
            ->setDetail('<p>Visita al centro de trabajo del <b>alumnado dual</b>.</p>');
        $manager->persist($contact);

        $expense = new TravelExpense();
        $expense
            ->setFromDateTime(\DateTime::createFromImmutable($visitDate->modify('-30 minutes')))
            ->setToDateTime(\DateTime::createFromImmutable($visitDate->modify('+2 hours')))
            ->setTeacher($this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR))
            ->setTravelRoute($this->getReference(EduFixtures::REF_TRAVEL_ROUTE . $prefix, TravelRoute::class))
            ->setAgreements(new ArrayCollection([$agreement1]))
            ->setDescription('Visita dual')
            ->setOtherExpenses(0);
        $manager->persist($expense);

        $this->addReference(self::REF_PROJECT . $prefix, $project);
        $this->addReference(self::REF_AGREEMENT_1 . $prefix, $agreement1);
        $this->addReference(self::REF_AGREEMENT_2 . $prefix, $agreement2);
    }

    private function agreement(
        ObjectManager $manager,
        Project $project,
        string $workcenterReference,
        StudentEnrollment $student,
        Person $workTutor,
        Teacher $educationalTutor,
        string $internalCode,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): Agreement {
        $agreement = new Agreement();
        $agreement
            ->setProject($project)
            ->setWorkcenter($this->getReference($workcenterReference, Workcenter::class))
            ->setStudentEnrollment($student)
            ->setWorkTutor($workTutor)
            ->setEducationalTutor($educationalTutor)
            ->setStartDate(\DateTime::createFromImmutable($start))
            ->setEndDate(\DateTime::createFromImmutable($end))
            ->setInternalCode($internalCode);
        $manager->persist($agreement);

        return $agreement;
    }
}
