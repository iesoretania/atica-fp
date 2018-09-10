<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

namespace AppBundle\Repository\ICT;

use AppBundle\Entity\Location;
use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityRepository;

class ElementRepository extends EntityRepository
{

    public function findByLocation(Location $location)
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.name')
            ->andWhere('e.location = :location')
            ->setParameter('location', $location)
            ->getQuery()
            ->getResult();
    }

    public function findInListByIdAndOrganization($items, Organization $organization)
    {
        return $this->createQueryBuilder('e')
            ->where('e.id IN (:items)')
            ->andWhere('e.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('e.name')
            ->addOrderBy('e.description')
            ->addOrderBy('e.serialNumber')
            ->getQuery()
            ->getResult();
    }
}
