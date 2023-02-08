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

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Subject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

class CriterionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Criterion::class);
    }

    public function findBySubject(Subject $subject)
    {
        return $this->createQueryBuilder('c')
            ->join('c.learningOutcome', 'lo')
            ->where('lo.subject = :subject')
            ->setParameter('subject', $subject)
            ->orderBy('lo.code')
            ->addOrderBy('c.code')
            ->getQuery()
            ->getResult();
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

    /**
     * @param LearningOutcome $learningOutcome
     * @return Criterion[]|Collection
     */
    private function findByLearningOutcome(LearningOutcome $learningOutcome)
    {
        return $this->createQueryBuilder('c')
            ->where('c.learningOutcome = :learning_outcome')
            ->setParameter('learning_outcome', $learningOutcome)
            ->orderBy('c.code')
            ->getQuery()
            ->getResult();
    }

    public function copyFromLearningOutcome(LearningOutcome $destination, LearningOutcome $source)
    {
        $criteria = $this->findByLearningOutcome($source);

        foreach ($criteria as $criterion) {
            $newCriterion = new Criterion();
            $newCriterion
                ->setLearningOutcome($destination)
                ->setDescription($criterion->getDescription())
                ->setCode($criterion->getCode())
                ->setName($criterion->getName());

            $this->getEntityManager()->persist($newCriterion);
        }
    }

    public function deleteFromLearningOutcome(LearningOutcome $item)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Criterion::class, 'c')
            ->where('c.learningOutcome = :item')
            ->setParameter('item', $item)
            ->getQuery()
            ->execute();
    }
}
