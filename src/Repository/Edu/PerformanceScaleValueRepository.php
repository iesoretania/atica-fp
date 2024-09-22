<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

use App\Entity\Edu\PerformanceScale;
use App\Entity\Edu\PerformanceScaleValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class PerformanceScaleValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceScaleValue::class);
    }

    public function findByPerformanceScale(?PerformanceScale $performanceScale)
    {
        return $this->createQueryBuilder('psv')
            ->where('psv.performanceScale = :performance_scale')
            ->setParameter('performance_scale', $performanceScale)
            ->orderBy('psv.numericGrade', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllInListByIdAndPerformanceScale(
        $items,
        PerformanceScale $performanceScale
    ) {
        return $this->createQueryBuilder('psv')
            ->where('psv IN (:items)')
            ->andWhere('psv.performanceScale = :performance_scale')
            ->setParameter('items', $items)
            ->setParameter('performance_scale', $performanceScale)
            ->orderBy('psv.numericGrade', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(PerformanceScaleValue::class, 'psv')
            ->where('psv IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function findByPerformanceScaleAndPartialStringQueryBuilder(PerformanceScale $performanceScale, mixed $q): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('psv')
            ->orderBy('psv.numericGrade', 'DESC');

        if ($q) {
            $queryBuilder
                ->where('psv.numericCode = :q')
                ->orWhere('psv.description LIKE :tq')
                ->orWhere('psv.notes LIKE :tq')
                ->setParameter('q', $q)
                ->setParameter('tq', '%'.$q.'%');
        }

        $queryBuilder
            ->andWhere('psv.performanceScale = :performance_scale')
            ->setParameter('performance_scale', $performanceScale);

        return $queryBuilder;
    }
}
