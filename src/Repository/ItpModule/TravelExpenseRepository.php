<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Repository\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\TravelExpense;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TravelExpenseRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
    )
    {
        parent::__construct($registry, TravelExpense::class);
    }

    public function findAllInListById($items)
    {
        return $this->createQueryBuilder('te')
            ->where('te IN (:items)')
            ->setParameter('items', $items)
            ->orderBy('te.fromDateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param TravelExpense[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(TravelExpense::class, 'te')
            ->where('te IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function createByTeacherOrderByDateTimeQueryBuilder(Teacher $teacher): QueryBuilder
    {
        return $this->createQueryBuilder('te')
            ->where('te.teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('te.fromDateTime', 'ASC')
            ->addOrderBy('te.toDateTime', 'ASC');
    }
    public function createStatsByTeacherOrderByDateTimeFilterQueryBuilder(Teacher $teacher, ?string $q): QueryBuilder
    {
        $qb = $this->createByTeacherOrderByDateTimeQueryBuilder($teacher)
            ->addSelect('tr')
            ->addSelect('COUNT(tp)')
            ->distinct()
            ->join('te.travelRoute', 'tr')
            ->leftJoin('te.trainingPrograms', 'tp')
            ->groupBy('te');

        if ($q) {
            $qb
                ->andWhere('tp.name LIKE :tq OR tr.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        return $qb;
    }

    public function findByTeacherOrderByDateTime(Teacher $teacher): array
    {
        return $this->createByTeacherOrderByDateTimeQueryBuilder($teacher)
            ->getQuery()
            ->getResult();
    }

    public function createTeacherTravelExpenseStatsByPersonAndAcademicYearQueryBuilder(
        AcademicYear $academicYear,
        bool $isManager,
        Person             $person,
        ?string            $q = null
    ): QueryBuilder {
        $studentProgramWorkcenters = $this->studentProgramWorkcenterRepository->findByAcademicYearPersonManagerAndQuery(
            $academicYear,
            $person,
            $isManager,
            $q
        );

        $em = $this->getEntityManager();

        $subQb = $em->createQueryBuilder()
            ->select('1')
            ->from(StudentProgramWorkcenter::class, 'spw')
            ->where('spw.educationalTutor = t OR spw.additionalEducationalTutor = t')
            ->andWhere('spw IN (:student_program_workcenters)');

        $qb = $em->createQueryBuilder()
            ->select('t')
            ->addSelect('p')
            ->addSelect('COUNT(DISTINCT te)')
            ->addSelect('SUM(tr.distance)')
            ->addSelect('SUM(tr.verified)')
            ->addSelect('SUM(DISTINCT te.otherExpenses)')
            ->from(Teacher::class, 't')
            ->join('t.person', 'p')
            ->leftJoin(TravelExpense::class, 'te', 'WITH', 'te.teacher = t')
            ->leftJoin('te.travelRoute', 'tr')
            ->where('t.academicYear = :academicYear')
            ->andWhere($em->createQueryBuilder()->expr()->exists($subQb->getDQL()))
            ->setParameter('academicYear', $academicYear)
            ->setParameter('student_program_workcenters', $studentProgramWorkcenters)
            ->groupBy('t')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName');

        return $qb;
    }
}
