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

namespace AppBundle\Repository;

use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityRepository;

class PersonRepository extends EntityRepository
{
    public function findAllByOrganizationSorted(Organization $organization)
    {
        return $this->createQueryBuilder('p')
            ->join('AppBundle:User', 'u', 'WITH', 'u.person = p')
            ->join('u.memberships', 'm')
            ->join('m.organization', 'o')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->andWhere('o.id = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getResult();
    }
}
