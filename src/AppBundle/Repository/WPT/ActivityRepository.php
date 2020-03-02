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

use AppBundle\Entity\WPT\Activity;
use AppBundle\Entity\WPT\Shift;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function findOneByCodeAndShift($code, Shift $shift)
    {
        return $this->createQueryBuilder('a')
            ->where('a.code = :code')
            ->andWhere('a.shift = :shift')
            ->setParameter('code', $code)
            ->setParameter('shift', $shift)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllInListByIdAndShift($items, Shift $shift)
    {
        return $this->createQueryBuilder('a')
        ->where('a IN (:items)')
        ->andWhere('a.shift = :shift')
        ->setParameter('items', $items)
        ->setParameter('shift', $shift)
        ->orderBy('a.code')
        ->getQuery()
        ->getResult();
    }

    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Activity::class, 'a')
            ->where('a IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
