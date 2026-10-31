<?php

namespace App\Tests\Fixtures;

use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\Criterion;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Subject;
use App\Entity\Edu\Teacher;
use App\Entity\Edu\TravelRoute;
use App\Entity\Workcenter;
use App\Entity\WptModule\Activity;
use App\Entity\WptModule\ActivityTracking;
use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Contact;
use App\Entity\WptModule\Shift;
use App\Entity\WptModule\TrackedWorkDay;
use App\Entity\WptModule\TravelExpense;
use App\Entity\WptModule\WorkDay;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Módulo de Formación en Centros de Trabajo (FCT / WPT): fase, actividades, convenios,
 * matrículas, jornadas, seguimiento diario, visitas y gastos de desplazamiento.
 */
class WptFixtures extends AbstractDemoFixture implements DependentFixtureInterface
{
    public const REF_SHIFT = 'wpt.shift.';
    public const REF_AGREEMENT_1 = 'wpt.agreement1.';
    public const REF_AGREEMENT_2 = 'wpt.agreement2.';
    public const REF_WORK_TUTOR_1 = 'person.work-tutor1.';
    public const REF_WORK_TUTOR_2 = 'person.work-tutor2.';

    public function getDependencies(): array
    {
        return [EduFixtures::class, CompanyFixtures::class];
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
        // Fase de prácticas y actividades
        $shift = new Shift();
        $shift
            ->setName('Formación en centros de trabajo')
            ->setHours(41000) // 410 horas, en centésimas
            ->setType('FCT')
            ->setQuarter(Shift::QUARTER_THIRD)
            ->setLocked(false)
            ->setSubject($this->getReference(EduFixtures::REF_SUBJECT . $prefix . '.PIN', Subject::class));
        $manager->persist($shift);

        $activities = [];
        foreach ([['A1', 'Instalación y configuración de equipos'], ['A2', 'Desarrollo de aplicaciones'], ['A3', 'Documentación y pruebas']] as [$code, $description]) {
            $activity = new Activity();
            $activity->setShift($shift)->setCode($code)->setDescription($description)->setCriteria(new ArrayCollection([
                $this->getReference(EduFixtures::REF_CRITERION . $prefix . '.PIN', Criterion::class)
            ]));
            $manager->persist($activity);
            $activities[] = $activity;
        }

        // Tutores laborales (no son profesores ni alumnos del centro)
        $workTutor1 = $this->createPerson($manager, DemoData::username($prefix, DemoData::ROLE_WORK_TUTOR), 'Tutor', 'Laboral Uno ' . ucfirst($prefix));
        $workTutor2 = $this->createPerson($manager, DemoData::username($prefix, DemoData::ROLE_WORK_TUTOR_2), 'Tutora', 'Laboral Dos ' . ucfirst($prefix));

        $this->addReference(self::REF_WORK_TUTOR_1 . $prefix, $workTutor1);
        $this->addReference(self::REF_WORK_TUTOR_2 . $prefix, $workTutor2);

        // Convenios
        [$start, $end] = DemoData::trainingPeriod();
        $agreement1 = $this->agreement($manager, $shift, 'Convenio con Tecnologías del Sur', CompanyFixtures::REF_WORKCENTER_MAIN, $start, $end);
        $agreement2 = $this->agreement($manager, $shift, 'Convenio con Consultoría del Norte', CompanyFixtures::REF_WORKCENTER_UBEDA, $start, $end);

        $enrollments = [
            [$agreement1, DemoData::ROLE_STUDENT_1, $workTutor1, DemoData::ROLE_GROUP_TUTOR],
            [$agreement1, DemoData::ROLE_STUDENT_2, $workTutor1, DemoData::ROLE_GROUP_TUTOR],
            [$agreement2, DemoData::ROLE_STUDENT_3, $workTutor2, DemoData::ROLE_TEACHER],
        ];
        $agreementEnrollments = [];
        foreach ($enrollments as [$agreement, $studentRole, $workTutor, $tutorRole]) {
            $enrollment = new AgreementEnrollment();
            $enrollment
                ->setAgreement($agreement)
                ->setStudentEnrollment($this->student($prefix, $studentRole))
                ->setWorkTutor($workTutor)
                ->setEducationalTutor($this->teacher($prefix, $tutorRole))
                ->setActivities(new ArrayCollection($activities));
            $manager->persist($enrollment);
            $agreementEnrollments[] = $enrollment;
        }

        // Jornadas (días laborables del período) y seguimiento diario hasta hoy
        $today = DemoData::today();
        foreach ([$agreement1, $agreement2] as $agreement) {
            foreach ($this->weekdays($start, $end) as $date) {
                $workDay = new WorkDay();
                // las horas se guardan en centésimas: 500 son 5 horas
                $workDay->setAgreement($agreement)->setDate(\DateTime::createFromImmutable($date))->setHours(500);
                $manager->persist($workDay);

                if ($date > $today) {
                    continue;
                }
                foreach ($agreementEnrollments as $enrollment) {
                    if ($enrollment->getAgreement() !== $agreement) {
                        continue;
                    }
                    $this->track($manager, $workDay, $enrollment, $activities, (int) $date->format('j') % 7 === 0);
                }
            }
        }

        // Visita del tutor docente al centro de trabajo y gasto de desplazamiento asociado
        $visitDate = max($start, $today->modify('-2 weeks'))->setTime(10, 0);
        $contact = new Contact();
        $contact
            ->setDateTime(\DateTime::createFromImmutable($visitDate))
            ->setTeacher($this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR))
            ->setWorkcenter($this->getReference(CompanyFixtures::REF_WORKCENTER_MAIN, Workcenter::class))
            ->setMethod($this->getReference(EduFixtures::REF_CONTACT_METHOD . $prefix, ContactMethod::class))
            ->setAgreements(new ArrayCollection([$agreement1]))
            ->setStudentEnrollments(new ArrayCollection([
                $this->student($prefix, DemoData::ROLE_STUDENT_1),
                $this->student($prefix, DemoData::ROLE_STUDENT_2),
            ]))
            ->setDetail('<p>Visita de seguimiento al <b>centro de trabajo</b>.</p>');
        $manager->persist($contact);

        $expense = new TravelExpense();
        $expense
            ->setFromDateTime(\DateTime::createFromImmutable($visitDate->modify('-30 minutes')))
            ->setToDateTime(\DateTime::createFromImmutable($visitDate->modify('+2 hours')))
            ->setTeacher($this->teacher($prefix, DemoData::ROLE_GROUP_TUTOR))
            ->setTravelRoute($this->getReference(EduFixtures::REF_TRAVEL_ROUTE . $prefix, TravelRoute::class))
            ->setAgreements(new ArrayCollection([$agreement1]))
            ->setDescription('Visita de seguimiento')
            ->setOtherExpenses(0);
        $manager->persist($expense);

        $this->addReference(self::REF_SHIFT . $prefix, $shift);
        $this->addReference(self::REF_AGREEMENT_1 . $prefix, $agreement1);
        $this->addReference(self::REF_AGREEMENT_2 . $prefix, $agreement2);
    }

    private function agreement(
        ObjectManager $manager,
        Shift $shift,
        string $name,
        string $workcenterReference,
        \DateTimeImmutable $start,
        \DateTimeImmutable $end
    ): Agreement {
        $agreement = new Agreement();
        $agreement
            ->setName($name)
            ->setShift($shift)
            ->setWorkcenter($this->getReference($workcenterReference, Workcenter::class))
            ->setStartDate(\DateTime::createFromImmutable($start))
            ->setEndDate(\DateTime::createFromImmutable($end))
            ->setSignDate(\DateTime::createFromImmutable($start->modify('-1 week')))
            ->setLocked(false);
        $manager->persist($agreement);

        return $agreement;
    }

    /**
     * @param list<Activity> $activities
     */
    private function track(
        ObjectManager $manager,
        WorkDay $workDay,
        AgreementEnrollment $enrollment,
        array $activities,
        bool $absent
    ): void {
        $tracked = new TrackedWorkDay();
        $tracked
            ->setWorkDay($workDay)
            ->setAgreementEnrollment($enrollment)
            ->setLocked(false)
            ->setAbsence($absent ? TrackedWorkDay::JUSTIFIED_ABSENCE : TrackedWorkDay::NO_ABSENCE);
        if (!$absent) {
            $tracked->setNotes('Jornada de prácticas')->setOtherActivities('Reunión de equipo');
        }
        $manager->persist($tracked);

        if ($absent) {
            return;
        }
        $tracking = new ActivityTracking();
        $tracking->setTrackedWorkDay($tracked)->setActivity($activities[0])->setHours(500)->setNotes('Trabajo realizado');
        $manager->persist($tracking);
    }

    /**
     * Días laborables (de lunes a viernes) entre dos fechas, ambas incluidas
     *
     * @return iterable<\DateTimeImmutable>
     */
    private function weekdays(\DateTimeImmutable $start, \DateTimeImmutable $end): iterable
    {
        for ($date = $start; $date <= $end; $date = $date->modify('+1 day')) {
            if ((int) $date->format('N') < 6) {
                yield $date;
            }
        }
    }
}
