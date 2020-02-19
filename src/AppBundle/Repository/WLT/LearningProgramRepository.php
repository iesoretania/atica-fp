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

use AppBundle\Entity\WLT\LearningProgram;
use AppBundle\Entity\WLT\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class LearningProgramRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearningProgram::class);
    }

    private function findByProjectQueryBuilder(Project $project)
    {
        return $this->createQueryBuilder('lp')
            ->join('lp.company', 'c')
            ->where('lp.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.name');
    }

    public function findByProject(Project $project)
    {
        return $this->findByProjectQueryBuilder($project)
            ->getQuery()
            ->getResult();
    }

    public function findAllInListByIdAndProject(
        $items,
        Project $project
    ) {
        return $this->findByProjectQueryBuilder($project)
            ->andWhere('lp.id IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param LearningProgram[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(LearningProgram::class, 'lp')
            ->where('lp IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
