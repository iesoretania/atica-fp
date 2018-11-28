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
use AppBundle\Entity\Person;
use AppBundle\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\ManagerRegistry;

class RoleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Role::class);
    }

    /**
     * @param Organization $organization
     * @param Person $person
     * @param string $roleName
     * @return bool
     */
    public function personHasRole(Organization $organization, Person $person, $roleName)
    {
        $role = $this->findOneBy([
            'organization' => $organization,
            'person' => $person,
            'role' => $roleName
        ]);

        return $role !== null;
    }
}