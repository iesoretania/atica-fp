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

namespace App\Repository\Edu;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Training;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly GradeRepository $gradeRepository)
    {
        parent::__construct($registry, Training::class);
    }

    /**
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
     * @return Training[]|Collection
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
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

    public function copyFromAcademicYear(AcademicYear $destination, AcademicYear $source): void
    {
        $trainings = $this->findByAcademicYear($source);
        foreach ($trainings as $training) {
            $newTraining = new Training();
            $newTraining->setAcademicYear($destination);
            $newTraining
                ->setName($training->getName())
                ->setInternalCode($training->getInternalCode());
            $this->getEntityManager()->persist($newTraining);
            $this->gradeRepository->copyFromTraining($newTraining, $training);
        }
    }

}
