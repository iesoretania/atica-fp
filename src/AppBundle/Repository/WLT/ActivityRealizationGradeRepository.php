<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

use AppBundle\Entity\WLT\ActivityRealizationGrade;
use AppBundle\Entity\WLT\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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
            ->andWhere('arg.academicYear = :academic_year')
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
}
