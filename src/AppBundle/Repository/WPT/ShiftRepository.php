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

namespace AppBundle\Repository\WPT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\WPT\Shift;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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
}
