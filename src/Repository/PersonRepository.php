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

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PersonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    public function findAllByOrganizationSorted(Organization $organization)
    {
        return $this->createQueryBuilder('p')
            ->join('App:User', 'u', 'WITH', 'u.person = p')
            ->join('u.memberships', 'm')
            ->join('m.organization', 'o')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->andWhere('o.id = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getResult();
    }

    public function findByPartialNameOrUniqueIdentifier($id, $pageLimit = 0)
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.firstName LIKE :tq')
            ->orWhere('p.lastName LIKE :tq')
            ->orWhere('p.uniqueIdentifier LIKE :tq')
            ->setParameter('tq', '%' . $id . '%')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if ($pageLimit) {
            $qb
                ->setMaxResults($pageLimit);
        }
        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findByUniqueIdentifier($id)
    {
        return $this->createQueryBuilder('p')
            ->where('p.uniqueIdentifier = :q')
            ->setParameter('q', $id)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }
}
