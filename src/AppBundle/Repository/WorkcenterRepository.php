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

namespace AppBundle\Repository;

use AppBundle\Entity\Company;
use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class WorkcenterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workcenter::class);
    }

    public function findByAcademicYearAndCompany(AcademicYear $academicYear, Company $company)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.academicYear = :academic_year')
            ->andWhere('w.company = :company')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('company', $company)
            ->orderBy('w.name')
            ->addOrderBy('w.city')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('w')
            ->join('w.company', 'c')
            ->andWhere('w.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('c.name')
            ->addOrderBy('w.name')
            ->addOrderBy('w.city')
            ->getQuery()
            ->getResult();
    }

    public function findAllInListByIdAndCompany(
        $items,
        Company $company
    ) {
        return $this->createQueryBuilder('w')
            ->where('w IN (:items)')
            ->andWhere('w.company = :company')
            ->setParameter('items', $items)
            ->setParameter('company', $company)
            ->orderBy('w.name')
            ->addOrderBy('w.city')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Workcenter::class, 'w')
            ->where('w IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
