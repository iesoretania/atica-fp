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
use App\Entity\Edu\Department;
use App\Entity\Edu\Teacher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DepartmentRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Department::class);
    }

    /**
     * @return Department[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('d')
            ->where('d.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('d.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @return Department[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('d')
            ->where('d.id IN (:items)')
            ->andWhere('d.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('d.name')
            ->getQuery()
            ->getResult();
    }

    public function findByTeacher(Teacher $teacher)
    {
        return $this->createQueryBuilder('d')
            ->where('d.head = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('d.name')
            ->getQuery()
            ->getResult();
    }
}
