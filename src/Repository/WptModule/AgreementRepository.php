<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace App\Repository\WptModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\Workcenter;
use App\Entity\WptModule\Agreement;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Shift;
use App\Entity\WptModule\WorkDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class AgreementRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly WorkDayRepository $workDayRepository,
        private readonly AgreementEnrollmentRepository $agreementEnrollmentRepository
    ) {
        parent::__construct($registry, Agreement::class);
    }

    public function findByShiftQueryBuilder(
        Shift $shift
    ) {
        return $this->createQueryBuilder('a')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->where('a.shift = :shift')
            ->setParameter('shift', $shift)
            ->orderBy('c.name')
            ->addOrderBy('w.name')
            ->addOrderBy('a.name');
    }

    /**
     * @return Agreement[]
     */
    public function findAllInListByIdAndShift(
        $items,
        Shift $shift
    ) {
        return $this->findByShiftQueryBuilder($shift)
            ->andWhere('a.id IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Agreement[]
     */
    public function findAllInListByNotIdAndShift(
        $items,
        Shift $shift
    ) {
        return $this->findByShiftQueryBuilder($shift)
            ->andWhere('a.id NOT IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYearQueryBuilder(
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('a')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.shift', 'sh')
            ->join('sh.subject', 's')
            ->join('s.grade', 'g')
            ->join('g.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('sh.name')
            ->addOrderBy('c.name')
            ->addOrderBy('w.name')
            ->addOrderBy('a.name');
    }

    /**
     * @return Agreement[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->andWhere('a.id IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Collection<int, Agreement>
     */
    public function findAllInListByNotIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->andWhere('a.id NOT IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYearAndStudentQueryBuilder(AcademicYear $academicYear, Person $student): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->distinct()
            ->join('a.agreementEnrollments', 'ae')
            ->join('ae.studentEnrollment', 'sr')
            ->join('ae.educationalTutor', 'et')
            ->where('sr.person = :student')
            ->andWhere('et.academicYear = :academic_year')
            ->setParameter('student', $student)
            ->setParameter('academic_year', $academicYear);
    }

    public function countAcademicYearAndStudentPerson(AcademicYear $academicYear, Person $student): int
    {
        try {
            return $this->findByAcademicYearAndStudentQueryBuilder($academicYear, $student)
                ->select('COUNT(a)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }

        return 0;
    }

    public function countAcademicYearAndWorkTutorPerson(AcademicYear $academicYear, Person $workTutor): int
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a)')
                ->join('a.workcenter', 'w')
                ->join(
                    AgreementEnrollment::class,
                    'ae',
                    'WITH',
                    'ae.agreement = a AND (ae.workTutor = :work_tutor OR ae.additionalWorkTutor = :work_tutor)'
                )
                ->join('a.shift', 'sh')
                ->join('sh.subject', 'su')
                ->join('su.grade', 'gr')
                ->join('gr.training', 't')
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('work_tutor', $workTutor)
                ->setParameter('academic_year', $academicYear)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }

        return 0;
    }

    public function countAcademicYearAndEducationalTutorPerson(AcademicYear $academicYear, Person $educationalTutor): int
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a)')
                ->join('a.shift', 'sh')
                ->join('sh.subject', 's')
                ->join('s.grade', 'gr')
                ->join('gr.training', 't')
                ->join(Teacher::class, 'te', 'WITH', 'te.person = :educational_tutor')
                ->join(
                    AgreementEnrollment::class,
                    'ae',
                    'WITH',
                    'ae.agreement = a AND (ae.educationalTutor = te OR ae.additionalEducationalTutor = te)'
                )
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('educational_tutor', $educationalTutor)
                ->setParameter('academic_year', $academicYear)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }

        return 0;
    }

    public function deleteFromList($list)
    {
        $this->agreementEnrollmentRepository->deleteFromAgreements($list);
        $this->getEntityManager()->createQueryBuilder()
            ->delete(WorkDay::class, 'wd')
            ->where('wd.agreement IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();

        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Agreement::class, 'a')
            ->where('a IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function updateDates(Agreement $agreement): void
    {
        $workDays = $this->workDayRepository->findByAgreement($agreement);
        if ((is_countable($workDays) ? count($workDays) : 0) === 0) {
            return;
        }
        /** @var WorkDay $first */
        $first = $workDays[0];
        /** @var WorkDay $last */
        $last = $workDays[(is_countable($workDays) ? count($workDays) : 0) - 1];
        $agreement
            ->setStartDate($first->getDate())
            ->setEndDate($last->getDate());

        $this->getEntityManager()->flush();
    }

    public function cloneCalendarFromAgreement(Agreement $destination, Agreement $source, $overwrite = false): void
    {
        $workDays = $this->workDayRepository->findByAgreement($source);
        if ((is_countable($workDays) ? count($workDays) : 0) === 0) {
            return;
        }

        $utc = new \DateTimeZone('UTC');

        /** @var WorkDay $workDay */
        foreach ($workDays as $workDay) {
            $newDate = new \DateTimeImmutable($workDay->getDate()->format('Y/m/d'), $utc);
            $newWorkDay = $this->workDayRepository->findOneByAgreementAndDate($destination, $newDate);
            if (null === $newWorkDay) {
                $newWorkDay = new WorkDay();
                $newWorkDay
                    ->setAgreement($destination)
                    ->setDate(new \DateTime($newDate->format('Y/m/d'), $utc))
                    ->setHours($workDay->getHours());
                $this->getEntityManager()->persist($newWorkDay);
            } elseif ($overwrite) {
                $newWorkDay->setHours($workDay->getHours());
            } else {
                $newWorkDay->setHours($newWorkDay->getHours() + $workDay->getHours());
            }
        }
    }
    public function findByWorkcenterAndTeacher(
        Workcenter $workcenter,
        Teacher $teacher
    ) {
        return $this->createQueryBuilder('a')
            ->distinct()
            ->join('a.agreementEnrollments', 'ae')
            ->andWhere('a.workcenter = :workcenter')
            ->andWhere('ae.educationalTutor = :teacher OR ae.additionalEducationalTutor = :teacher')
            ->setParameter('workcenter', $workcenter)
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    public function findByIds($items)
    {
        return $this->createQueryBuilder('a')
            ->where('a IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    public function findByDepartmentHeadPerson(Person $person)
    {
        return $this->createQueryBuilder('a')
            ->distinct(true)
            ->join('a.shift', 'sh')
            ->join('sh.subject', 'su')
            ->join('su.grade', 'gr')
            ->join('gr.training', 't')
            ->join('t.department', 'd')
            ->join('d.head', 'h')
            ->where('h.person = :person')
            ->setParameter('person', $person)
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYearAndEducationalTutorOrDepartmentHead(AcademicYear $academicYear, Teacher $teacher)
    {

        return $this->createQueryBuilder('a')
            ->distinct(true)
            ->join('a.agreementEnrollments', 'ae')
            ->join('a.shift', 'sh')
            ->join('sh.subject', 'su')
            ->join('su.grade', 'gr')
            ->join('gr.training', 't')
            ->leftJoin('t.department', 'd')
            ->leftJoin('d.head', 'h')
            ->where('t.academicYear = :academic_year')
            ->andWhere('h.person = :person OR ae.educationalTutor = :teacher OR ae.additionalEducationalTutor = :teacher')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $teacher->getPerson())
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    public function deleteFromShifts($items): void
    {
        /** @var Shift $shift */
        foreach ($items as $shift) {
            $this->deleteFromList($shift->getAgreements());
        }
    }
}
