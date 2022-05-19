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

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkcenterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workcenter::class);
    }

    public function findByCompany(
        Company $company
    ) {
        return $this->createQueryBuilder('w')
            ->andWhere('w.company = :company')
            ->setParameter('company', $company)
            ->orderBy('w.name')
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

    public function findAllSorted()
    {
        return $this->createQueryBuilder('w')
            ->join('w.company', 'c')
            ->orderBy('c.name')
            ->addOrderBy('w.name')
            ->addOrderBy('w.city')
            ->getQuery()
            ->getResult();
    }
}
