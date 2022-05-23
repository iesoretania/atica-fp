<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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

namespace App\Repository\WPT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Organization;
use App\Entity\WPT\Shift;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ShiftRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shift::class);
    }

    public function findByAcademicYear(
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('s')
            ->join('s.grade', 'gr')
            ->join('gr.training', 'tr')
            ->where('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();
    }

    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('s')
            ->where('s IN (:items)')
            ->join('s.grade', 'gr')
            ->join('gr.training', 'tr')
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();
    }

    public function findByIds(
        $items
    ) {
        return $this->createQueryBuilder('s')
            ->where('s IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Shift::class, 's')
            ->where('s IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function findRelatedByOrganizationButOne(Organization $organization, Shift $shift)
    {
        return $this->createQueryBuilder('s')
            ->join('s.subject', 'sub')
            ->join('sub.grade', 'gr')
            ->join('gr.training', 't')
            ->join('t.academicYear', 'ay')
            ->where('s != :shift')
            ->andWhere('sub.internalCode = :subject_internal_code')
            ->andWhere('gr.internalCode = :grade_internal_code')
            ->andWhere('ay.organization = :organization')
            ->orderBy('ay.description', 'DESC')
            ->addOrderBy('s.name')
            ->setParameter('organization', $organization)
            ->setParameter('subject_internal_code', $shift->getSubject()->getInternalCode())
            ->setParameter(
                'grade_internal_code',
                $shift->getSubject()->getGrade()->getInternalCode()
            )
            ->setParameter('organization', $organization)
            ->setParameter('shift', $shift)
            ->getQuery()
            ->getResult();
    }

    public function findByHeadOfDepartment(Teacher $teacher)
    {
        return $this->createQueryBuilder('s')
            ->join('s.subject', 'su')
            ->join('su.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.department', 'd')
            ->where('d.head = :head')
            ->setParameter('head', $teacher)
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();
    }
}
