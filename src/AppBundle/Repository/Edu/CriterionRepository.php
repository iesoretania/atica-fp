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

use AppBundle\Entity\Edu\Criterion;
use AppBundle\Entity\Edu\LearningOutcome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class CriterionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Criterion::class);
    }

    public function findAllInListByIdAndLearningOutcome(
        $items,
        LearningOutcome $learningOutcome
    ) {
        return $this->createQueryBuilder('c')
            ->where('c IN (:items)')
            ->andWhere('c.learningOutcome = :learning_outcome')
            ->setParameter('items', $items)
            ->setParameter('learning_outcome', $learningOutcome)
            ->orderBy('c.code')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCodeAndLearningOutcome($code, LearningOutcome $learningOutcome)
    {
        return $this->createQueryBuilder('c')
            ->where('c.code = :code')
            ->andWhere('c.learningOutcome = :learning_outcome')
            ->setParameter('code', $code)
            ->setParameter('learning_outcome', $learningOutcome)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Criterion::class, 'c')
            ->where('c IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
