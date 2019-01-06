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
use AppBundle\Entity\WLT\LearningProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class LearningProgramRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearningProgram::class);
    }

    /**
     * @param AcademicYear $academicYear
     * @return QueryBuilder
     */
    private function findByAcademicYearQueryBuilder(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('lp')
            ->join('lp.company', 'c')
            ->join('lp.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('c.name')
            ->addOrderBy('t.name');
    }

    /**
     * @param AcademicYear $academicYear
     * @return LearningProgram[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @param AcademicYear $academicYear
     * @return LearningProgram[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->andWhere('lp.id IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param LearningProgram[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(LearningProgram::class, 'lp')
            ->where('lp IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
