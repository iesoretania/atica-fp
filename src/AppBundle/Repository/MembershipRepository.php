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

namespace AppBundle\Repository;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Organization;
use AppBundle\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    public function deleteOldMemberships(\DateTime $date)
    {
        $this->createQueryBuilder('m')
            ->delete(Membership::class, 'm')
            ->where('m.validUntil < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Organization $organization
     * @param User $user
     * @param \DateTime $fromDate
     * @param \DateTime|null $toDate
     *
     * @return Membership
     */
    public function addNewOrganizationMembership(
        Organization $organization,
        User $user,
        \DateTime $fromDate,
        \DateTime $toDate = null
    ) {
        if ($user->getId()) {
            try {
                $membership = $this->createQueryBuilder('m')
                    ->where('m.organization = :organization')
                    ->andWhere('m.user = :user')
                    ->andWhere('m.validFrom = :from_date')
                    ->andWhere('m.validUntil = :to_date')
                    ->setParameter('organization', $organization)
                    ->setParameter('user', $user)
                    ->setParameter('from_date', $fromDate)
                    ->setParameter('to_date', $toDate)
                    ->getQuery()
                    ->getOneOrNullResult();

            } catch (NonUniqueResultException $e) {
                $membership = null;
            }
        } else {
            $membership = null;
        }
        if (null === $membership) {
            $membership = new Membership();
            $membership
                ->setOrganization($organization)
                ->setUser($user)
                ->setValidFrom($fromDate)
                ->setValidUntil($toDate);

            $this->getEntityManager()->persist($membership);
        }

        return $membership;
    }
}
