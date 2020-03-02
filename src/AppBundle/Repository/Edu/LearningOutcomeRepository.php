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

use AppBundle\Entity\Edu\LearningOutcome;
use AppBundle\Entity\Edu\Subject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class LearningOutcomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearningOutcome::class);
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
}
