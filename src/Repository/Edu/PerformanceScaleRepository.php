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
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class PerformanceScaleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceScale::class);
    }

    public function findByOrganization(?Organization $organization): array
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('ps.description')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndPartialStringQueryBuilder(Organization $organization, mixed $q): QueryBuilder
    {
        return $this->createQueryBuilder('ps')
            ->where('ps.organization = :organization')
            ->andWhere('ps.description LIKE :q')
            ->setParameter('organization', $organization)
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('ps.description');
    }

    public function findAllInListByIdAndOrganization(array $items, Organization $organization): array
    {
        return $this->createQueryBuilder('ps')
            ->where('ps IN (:items)')
            ->andWhere('ps.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('ps.description', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList(array $items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(PerformanceScale::class, 'ps')
            ->where('ps IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
