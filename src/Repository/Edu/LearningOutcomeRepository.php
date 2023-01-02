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

use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Subject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

class LearningOutcomeRepository extends ServiceEntityRepository
{
    private $criterionRepository;

    public function __construct(ManagerRegistry $registry, CriterionRepository $criterionRepository)
    {
        parent::__construct($registry, LearningOutcome::class);
        $this->criterionRepository = $criterionRepository;
    }

    public function findAllInListByIdAndSubject(
        $items,
        Subject $subject
    ) {
        return $this->createQueryBuilder('l')
            ->where('l IN (:items)')
            ->andWhere('l.subject = :subject')
            ->setParameter('items', $items)
            ->setParameter('subject', $subject)
            ->orderBy('l.code')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCodeAndSubject($code, Subject $subject)
    {
        return $this->createQueryBuilder('l')
            ->where('l.code = :code')
            ->andWhere('l.subject = :subject')
            ->setParameter('code', $code)
            ->setParameter('subject', $subject)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(LearningOutcome::class, 'l')
            ->where('l IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function findByGroups($groups)
    {
        return $this->createQueryBuilder('lo')
            ->join('lo.subject', 's')
            ->join('s.grade', 'g')
            ->join('g.groups', 'gr')
            ->where('gr IN (:groups)')
            ->setParameter('groups', $groups)
            ->addOrderBy('g.name')
            ->addOrderBy('s.name')
            ->addOrderBy('lo.code')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Subject $subject
     * @return LearningOutcome[]|Collection
     */
    public function findBySubject(Subject $subject)
    {
        return $this->createQueryBuilder('lo')
            ->where('lo.subject = :subject')
            ->setParameter('subject', $subject)
            ->getQuery()
            ->getResult();
    }

    public function copyFromSubject(Subject $newSubject, Subject $source)
    {
        $learningOutcomes = $this->findBySubject($source);
        foreach ($learningOutcomes as $learningOutcome) {
            $newLearningOutcome = new LearningOutcome();
            $newLearningOutcome
                ->setSubject($newSubject)
                ->setCode($learningOutcome->getCode())
                ->setDescription($learningOutcome->getDescription());

            $this->getEntityManager()->persist($newLearningOutcome);

            $this->criterionRepository->copyFromLearningOutcome($newLearningOutcome, $learningOutcome);
        }
    }
}
