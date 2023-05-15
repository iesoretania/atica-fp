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

namespace App\Repository\WLT;

use App\Entity\WLT\ActivityRealizationGrade;
use App\Entity\WLT\AgreementActivityRealization;
use App\Entity\WLT\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActivityRealizationGradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityRealizationGrade::class);
    }

    public function findAllInListByIdAndProject(
        $items,
        Project $project
    ) {
        return $this->createQueryBuilder('arg')
            ->where('arg IN (:items)')
            ->andWhere('arg.project = :project')
            ->setParameter('items', $items)
            ->setParameter('project', $project)
            ->orderBy('arg.numericGrade', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(ActivityRealizationGrade::class, 'arg')
            ->where('arg IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function findByProject(Project $project)
    {
        return $this->createQueryBuilder('arg')
            ->where('arg.project = :project')
            ->setParameter('project', $project)
            ->orderBy('arg.numericGrade', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromProjects($items)
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete(AgreementActivityRealization::class, 'aar')
            ->where('aar.grade IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(ActivityRealizationGrade::class, 'arg')
            ->where('arg.project IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
