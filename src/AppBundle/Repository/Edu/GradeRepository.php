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
use AppBundle\Entity\Edu\Grade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class GradeRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Grade::class);
    }

    /**
     * @param AcademicYear $academicYear
     * @return Grade[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getResult();
    }
}
