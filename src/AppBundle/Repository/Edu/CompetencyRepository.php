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

namespace AppBundle\Repository\Edu;

use AppBundle\Entity\Edu\Competency;
use AppBundle\Entity\Edu\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class CompetencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Competency::class);
    }

    public function findAllInListByIdAndTraining(
        $items,
        Training $training
    ) {
        return $this->createQueryBuilder('c')
            ->where('c IN (:items)')
            ->andWhere('c.training = :training')
            ->setParameter('items', $items)
            ->setParameter('training', $training)
            ->orderBy('c.code')
            ->getQuery()
            ->getResult();
    }

    public function findByTraining(Training $training)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.training = :training')
            ->setParameter('training', $training)
            ->orderBy('c.code')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCodeAndTraining($code, Training $training)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.code = :code')
            ->andWhere('c.training = :training')
            ->setParameter('code', $code)
            ->setParameter('training', $training)
            ->orderBy('c.code')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Competency::class, 'c')
            ->where('c IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
