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

namespace AppBundle\Repository\Edu;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class TrainingRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    /**
     * @param AcademicYear $academicYear
     * @return QueryBuilder
     */
    private function findByAcademicYearQueryBuilder(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('t')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('t.name');
    }

    /**
     * @param AcademicYear $academicYear
     * @return Training[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @return Training[]
     */
    public function findByAcademicYearAndWLT(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->andWhere('t.workLinked = :work_linked')
            ->setParameter('work_linked', true)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @param AcademicYear $academicYear
     * @return Training[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('t')
            ->where('t.id IN (:items)')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('t.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $departmentHead
     * @return QueryBuilder
     */
    private function findByAcademicYearAndDepartmentHeadQueryBuilder(
        AcademicYear $academicYear,
        Person $departmentHead
    ) {
        return $this->createQueryBuilder('t')
            ->join('t.department', 'd')
            ->join('d.head', 'te')
            ->andWhere('t.academicYear = :academic_year')
            ->andWhere('te.person = :department_head')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('department_head', $departmentHead);
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $departmentHead
     * @return Training[]
     */
    public function findByAcademicYearAndDepartmentHead(
        AcademicYear $academicYear,
        Person $departmentHead
    ) {
        return $this->findByAcademicYearAndDepartmentHeadQueryBuilder($academicYear, $departmentHead)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $departmentHead
     * @return int
     */
    public function countAcademicYearAndDepartmentHead(
        AcademicYear $academicYear,
        Person $departmentHead
    ) {
        return $this->findByAcademicYearAndDepartmentHeadQueryBuilder($academicYear, $departmentHead)
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();
    }

}
