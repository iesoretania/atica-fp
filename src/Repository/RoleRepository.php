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
use App\Entity\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

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

    public function findByOrganizationAndRole(Organization $organization, $roleName)
    {
        return $this->findBy([
            'organization' => $organization,
            'role' => $roleName
        ]);
    }

    public function updateOrganizationRoles(Organization $organization, $rolesData)
    {
        foreach ($rolesData as $roleName => $roleData) {

            // inicialmente copiar quien tenía originalmente el rol
            $toRemove = new ArrayCollection(array_map(function (Role $role) {
                return $role->getPerson();
            }, $this->findByOrganizationAndRole($organization, $roleName)));

            $toAdd = [];

            // si una persona mantiene el rol, se elimina de la lista de eliminación
            // si es una persona nueva, se añade a la lista de nuevos
            foreach ($roleData as $person) {
                if ($toRemove->contains($person)) {
                    $toRemove->removeElement($person);
                } else {
                    $toAdd[] = $person;
                }
            }

            // eliminar las asignaciones de rol que queden en la lista
            $this->createQueryBuilder('r')
                ->delete(Role::class, 'r')
                ->where('r.organization = :organization')
                ->andWhere('r.role = :role_name')
                ->andWhere('r.person IN (:persons)')
                ->setParameter('organization', $organization)
                ->setParameter('role_name', $roleName)
                ->setParameter('persons', $toRemove)
                ->getQuery()
                ->execute();

            // crear las nuevas asignaciones de rol
            foreach ($toAdd as $person) {
                $role = new Role();
                $role
                    ->setOrganization($organization)
                    ->setRole($roleName)
                    ->setPerson($person);
                $this->getEntityManager()->persist($role);
            }
        }
    }
}
