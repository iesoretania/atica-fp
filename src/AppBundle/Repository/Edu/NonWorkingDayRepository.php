<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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
use AppBundle\Entity\Edu\NonWorkingDay;
use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class NonWorkingDayRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NonWorkingDay::class);
    }

    /**
     * @param AcademicYear $academicYear
     * @return NonWorkingDay[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('n')
            ->where('n.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('n.date')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @param AcademicYear $academicYear
     * @return NonWorkingDay[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('n')
            ->where('n.id IN (:items)')
            ->andWhere('n.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('n.date')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param NonWorkingDay[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(NonWorkingDay::class, 'n')
            ->where('n IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
