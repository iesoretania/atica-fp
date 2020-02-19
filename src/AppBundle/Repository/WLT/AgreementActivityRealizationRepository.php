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

namespace AppBundle\Repository\WLT;

use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\AgreementActivityRealization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class AgreementActivityRealizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgreementActivityRealization::class);
    }

    public function findByAgreementSorted(Agreement $agreement)
    {
        return $this->createQueryBuilder('aar')
            ->addSelect('ar')
            ->addSelect('gr')
            ->join('aar.activityRealization', 'ar')
            ->leftJoin('aar.grade', 'gr')
            ->where('aar.agreement = :agreement')
            ->setParameter('agreement', $agreement)
            ->orderBy('ar.code')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList(Agreement $agreement, $items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(AgreementActivityRealization::class, 'a')
            ->where('a.agreement = :agreement')
            ->andWhere('a.activityRealization IN (:items)')
            ->setParameter('agreement', $agreement)
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
