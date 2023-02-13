<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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
use App\Entity\Edu\Group;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findByOrganization(Organization $organization)
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.grade', 'gr')
            ->innerJoin('gr.training', 't')
            ->join('t.academicYear', 'a')
            ->where('a.organization = :organization')
            ->setParameter('organization', $organization)
            ->addOrderBy('a.description', 'DESC')
            ->addOrderBy('g.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @return QueryBuilder
     */
    public function findByAcademicYearQueryBuilder(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.grade', 'gr')
            ->innerJoin('gr.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);
    }

    /**
     * @param AcademicYear $academicYear
     * @return Group[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @param string $internalCode
     * @return Group|null
     */
    public function findOneByAcademicYearAndInternalCode(AcademicYear $academicYear, $internalCode)
    {
        try {
            return $this->findByAcademicYearQueryBuilder($academicYear)
                ->andWhere('g.internalCode = :internal_code')
                ->setParameter('internal_code', $internalCode)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param AcademicYear $academicYear
     * @param string $code
     * @return Group|null
     */
    public function findOneByAcademicYearAndNameOrInternalCode(AcademicYear $academicYear, $code)
    {
        try {
            return $this->findByAcademicYearQueryBuilder($academicYear)
                ->andWhere('g.internalCode = :code OR g.name = :code')
                ->setParameter('code', $code)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param $items
     * @param AcademicYear $academicYear
     * @return Group[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('g.id IN (:items)')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('g.name')
            ->getQuery()
            ->getResult();
    }
}
