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

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function createEducationalOrganization()
    {
        $organization = new Organization();

        $year = (date('n') < 9) ? (date('Y') - 1) : date('Y');
        $startDate = new \DateTime($year . '/09/01');
        $endDate = new \DateTime(($year + 1) . '/08/31');

        $academicYear = new AcademicYear();
        $academicYear
            ->setOrganization($organization)
            ->setStartDate($startDate)
            ->setEndDate($endDate)
            ->setDescription($year . '-' . ($year + 1));

        $this->getEntityManager()->persist($organization);
        $this->getEntityManager()->persist($academicYear);

        $organization
            ->setCurrentAcademicYear($academicYear);

        return $organization;
    }

    /**
     * Devuelve las organizaciones a las que pertenece el usuario en la fecha indicada.
     * Si no se especifica fecha, se devuelven todas a las que pertenece.
     *
     * @param Person $user
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function getMembershipByPersonQueryBuilder(Person $user)
    {
        if ($user->isGlobalAdministrator()) {
            return $this->createQueryBuilder('o')
                ->orderBy('o.name');
        }

        $query = $this->createQueryBuilder('o');
        return $query
            //->andWhere('m.user = :user')
            //->setParameter('user', $user)
            ->distinct()
            ->orderBy('o.name');
    }

    /**
     * Devuelve la primera organización a la que pertenece el usuario indicado en la fecha pasada
     * como parámetro.
     *
     * @param Person $user
     * @return Organization|null
     */
    public function findFirstByUserOrNull(Person $user)
    {
        $query = $this->getMembershipByPersonQueryBuilder($user);
        return $query
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Devuelve si el usuario pertenece a la organización
     */
    public function findByUserAndOrganization(Person $user, Organization $organization) : bool
    {
        $query = $this->getMembershipByPersonQueryBuilder($user);
        return $query
            ->select('COUNT(o)')
            ->andWhere('o = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleColumnResult() > 0;
    }

    /**
     * Devuelve el número de organizaciones a las que pertenece un usuario en una fecha determinada.
     *
     * @param Person $user
     * @return int
     */
    public function countOrganizationsByPerson(Person $user)
    {
        $query = $this->getMembershipByPersonQueryBuilder($user);
        return $query
            ->select('COUNT(DISTINCT o)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Pasado un array de ids de organizaciones, devolver la lista de objetos exceptuando la organización actual
     * @param $items
     * @param Organization $organization
     * @return array
     */
    public function findAllInListByIdButCurrent($items, Organization $organization)
    {
        return $this->createQueryBuilder('o')
            ->where('o.id IN (:items)')
            ->andWhere('o != :current')
            ->setParameter('items', $items)
            ->setParameter('current', $organization)
            ->orderBy('o.code')
            ->getQuery()
            ->getResult();
    }
}
