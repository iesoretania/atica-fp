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

use AppBundle\Entity\Organization;
use AppBundle\Entity\Person;
use AppBundle\Entity\WLT\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function countByOrganizationAndManager(Organization $organization, Person $manager)
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->where('p.organization = :organization')
            ->andWhere('p.manager = :manager')
            ->setParameter('organization', $organization)
            ->setParameter('manager', $manager)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllInListByIdAndOrganization(
        $items,
        Organization $organization
    ) {
        return $this->createQueryBuilder('p')
            ->where('p IN (:items)')
            ->andWhere('p.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganization(
        Organization $organization
    ) {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findByGroups(
        $groups
    ) {
        return $this->createQueryBuilder('p')
            ->distinct(true)
            ->join('p.groups', 'g')
            ->andWhere('g IN (:groups)')
            ->setParameter('groups', $groups)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
