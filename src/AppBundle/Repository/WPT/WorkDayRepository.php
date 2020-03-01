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

use AppBundle\Entity\WPT\Agreement;
use AppBundle\Entity\WPT\WorkDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class WorkDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkDay::class);
    }

    public function findByAgreement(Agreement $agreement)
    {
        return $this->createQueryBuilder('wr')
            ->where('wr.agreement = :agreement')
            ->setParameter('agreement', $agreement)
            ->addOrderBy('wr.date')
            ->getQuery()
            ->getResult();
    }

    public function findOneByAgreementAndDate(Agreement $agreement, \DateTime $date)
    {
        return $this->createQueryBuilder('wr')
            ->where('wr.agreement = :agreement')
            ->andWhere('wr.date = :date')
            ->setParameter('agreement', $agreement)
            ->setParameter('date', $date)
            ->addOrderBy('wr.date')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
