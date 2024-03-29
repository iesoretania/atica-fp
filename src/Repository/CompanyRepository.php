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

namespace App\Repository;

use App\Entity\Company;
use App\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findAllInListById(
        $items
    ) {
        return $this->createQueryBuilder('c')
            ->where('c IN (:items)')
            ->setParameter('items', $items)
            ->orderBy('c.code')
            ->addOrderBy('c.name')
            ->addOrderBy('c.city')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        $workcenters = $this->getEntityManager()->createQueryBuilder()
            ->select(Workcenter::class, 'wc')
            ->join('wc.company', 'c')
            ->where('c IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();

        $this->getEntityManager()->createQueryBuilder()
            ->delete(Workcenter::class, 'wc')
            ->where('wc IN (:items)')
            ->setParameter('items', $workcenters)
            ->getQuery()
            ->execute();

        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Company::class, 'c')
            ->where('c IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
