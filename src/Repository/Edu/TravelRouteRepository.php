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

use App\Entity\Edu\TravelRoute;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TravelRouteRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TravelRoute::class);
    }


    public function findByOrganization(Organization $organization)
    {
        return $this->createQueryBuilder('tr')
            ->where('tr.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('tr.description', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllInListById($items)
    {
        return $this->createQueryBuilder('tr')
            ->where('tr IN (:items)')
            ->setParameter('items', $items)
            ->orderBy('tr.description', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param TravelRoute[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(TravelRoute::class, 'tr')
            ->where('tr IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function findByOrganizationAndQuery(Organization $organization, string $term)
    {
        return $this->createQueryBuilder('tr')
            ->where('tr.organization = :organization')
            ->andWhere('tr.description LIKE :pq')
            ->setParameter('organization', $organization)
            ->setParameter('pq', '%' . $term . '%')
            ->orderBy('tr.description')
            ->getQuery()
            ->execute();
    }
}
