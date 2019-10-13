<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Repository\WLT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Person;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\Meeting;
use AppBundle\Entity\WLT\WorkDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class AgreementRepository extends ServiceEntityRepository
{
    private $workDayRepository;

    public function __construct(ManagerRegistry $registry, WorkDayRepository $workDayRepository)
    {
        parent::__construct($registry, Agreement::class);
        $this->workDayRepository = $workDayRepository;
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $student
     *
     * @return QueryBuilder
     */
    public function findByAcademicYearAndStudentQueryBuilder(AcademicYear $academicYear, Person $student)
    {
        return $this->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'sr')
            ->join('sr.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('sr.person = :student')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('student', $student)
            ->setParameter('academic_year', $academicYear);
    }

    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('a')
            ->addSelect('sr')
            ->addSelect('p')
            ->addSelect('g')
            ->join('a.studentEnrollment', 'sr')
            ->join('sr.person', 'p')
            ->join('sr.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $student
     *
     * @return int
     */
    public function countAcademicYearAndStudentPerson(AcademicYear $academicYear, Person $student)
    {
        try {
            return $this->findByAcademicYearAndStudentQueryBuilder($academicYear, $student)
                ->select('COUNT(a)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return 0;
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $workTutor
     *
     * @return int
     */
    public function countAcademicYearAndWorkTutorPerson(AcademicYear $academicYear, Person $workTutor)
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a)')
                ->join('a.workcenter', 'w')
                ->join('a.studentEnrollment', 'sr')
                ->join('sr.group', 'g')
                ->join('g.grade', 'gr')
                ->join('gr.training', 't')
                ->where('a.workTutor = :work_tutor')
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('work_tutor', $workTutor)
                ->setParameter('academic_year', $academicYear)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return 0;
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $educationalTutor
     *
     * @return int
     */
    public function countAcademicYearAndEducationalTutorPerson(AcademicYear $academicYear, Person $educationalTutor)
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a)')
                ->join('a.workcenter', 'w')
                ->join('a.studentEnrollment', 'sr')
                ->join('sr.group', 'g')
                ->join('g.grade', 'gr')
                ->join('gr.training', 't')
                ->where('a.educationalTutor = :educational_tutor')
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('educational_tutor', $educationalTutor)
                ->setParameter('academic_year', $academicYear)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return 0;
    }

    /**
     * @param $items
     * @param Organization $organization
     * @return Agreement[]
     */
    public function findAllInListByIdAndOrganization(
        $items,
        Organization $organization
    ) {
        return $this->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('se.person', 'p')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->where('a.id IN (:items)')
            ->andWhere('ay.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @param Organization $organization
     * @return Agreement[]
     */
    public function findAllInListByNotIdAndOrganization(
        $items,
        Organization $organization
    ) {
        return $this->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('se.person', 'p')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->where('a.id NOT IN (:items)')
            ->andWhere('ay.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function updateDates(Agreement $agreement)
    {
        $workDays = $this->workDayRepository->findByAgreement($agreement);
        if (count($workDays) === 0) {
            return;
        }
        /** @var WorkDay $first */
        $first = $workDays[0];
        /** @var WorkDay $last */
        $last = $workDays[count($workDays) - 1];
        $agreement
            ->setStartDate($first->getDate())
            ->setEndDate($last->getDate());

        $this->getEntityManager()->flush();
    }

    public function cloneCalendarFromAgreement(Agreement $destination, Agreement $source, $overwrite = false)
    {
        $workDays = $this->workDayRepository->findByAgreement($source);
        if (count($workDays) === 0) {
            return;
        }

        /** @var WorkDay $workDay */
        foreach ($workDays as $workDay) {
            $newWorkDay = $this->workDayRepository->findOneByAgreementAndDate($destination, $workDay->getDate());
            if (null === $newWorkDay) {
                $newWorkDay = new WorkDay();
                $newWorkDay
                    ->setAgreement($destination)
                    ->setDate($workDay->getDate())
                    ->setHours($workDay->getHours());
                $this->getEntityManager()->persist($newWorkDay);
            } elseif ($overwrite) {
                $newWorkDay->setHours($workDay->getHours());
            } else {
                $newWorkDay->setHours($newWorkDay->getHours() + $workDay->getHours());
            }
        }
    }

    /**
     * @param Agreement[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Agreement::class, 'a')
            ->where('a IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function meetingStatsByTeacher(Teacher $teacher)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('SUM(CASE WHEN DATE(m.dateTime) >= a.startDate AND DATE(m.dateTime) <= a.endDate THEN 1 ELSE 0 END)')
            ->addSelect('COUNT(m.dateTime)')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->leftJoin(Meeting::class, 'm', 'WITH', 'se MEMBER OF m.studentEnrollments')
            ->leftJoin(Teacher::class, 't', 'WITH', 't MEMBER OF m.teachers')
            ->groupBy('a')
            ->andWhere('t = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->getQuery()
            ->getResult();
    }

    public function attendanceStatsByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('SUM(wd.hours)')
            ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 1 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('COUNT(wd.hours)')
            ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 1 THEN wd.locked ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN wd.locked ELSE 0 END)')
            ->leftJoin('a.workDays', 'wd')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name')
            ->where('w.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getResult();
    }
}
