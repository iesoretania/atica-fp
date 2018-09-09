<?php

namespace AppBundle\Repository\ICT;

use Doctrine\ORM\EntityRepository;

class PriorityRepository extends EntityRepository
{
    public function findAllSortedByPriority()
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.levelNumber')
            ->getQuery()
            ->getResult();
    }
}
