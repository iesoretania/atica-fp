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

namespace App\Repository;

use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PersonRepository extends ServiceEntityRepository implements UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Person::class);
    }

    final public function findByPartialNameOrUniqueIdentifier($id, $pageLimit = 0)
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

    final public function findOneByUniqueIdentifiers($id, $id2 = null): ?Person
    {
        $qb = $this->createQueryBuilder('p')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->setMaxResults(1)
            ->setParameter('q', $id);

        if ($id2 !== null && $id2 !== '') {
            $qb
                ->where('p.uniqueIdentifier IN (:q, :q2)')
                ->setParameter('q2', $id2);
        } else {
            $qb
                ->where('p.uniqueIdentifier = :q');
        }

        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }

    final public function findOneByUniqueIdentifierOrUsernameOrEmailAddress($id): ?Person
    {
        return $this->createQueryBuilder('p')
            ->where('p.uniqueIdentifier = :q OR p.emailAddress = :q OR p.loginUsername = :q')
            ->setParameter('q', $id)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Loads the user for the given username.
     *
     * This method must return null if the user is not found.
     *
     * @param string $username The username
     *
     * @return UserInterface|null
     */
   final public function loadUserByUsername($username): ?UserInterface
    {
        if ($username === '' || $username === null) {
            return null;
        }
        try {
            return $this->getEntityManager()
                ->createQuery('SELECT p FROM App:Person p
                           WHERE p.loginUsername = :username
                           OR p.emailAddress = :username')
                ->setParameters([
                                    'username' => $username
                                ])
                ->setMaxResults(1)
                ->getOneOrNullResult();
        }
        catch(NonUniqueResultException) {
            return null;
        }
    }

    /**
     * @param $identifier
     * @return UserInterface|null
     */
    final public function loadUserByIdentifier($identifier): ?UserInterface
    {
        return $this->loadUserByUsername($identifier);
    }

    /**
     * @param UserInterface $user
     * @return null|UserInterface
     */
    final public function refreshUser(UserInterface $user): ?UserInterface
    {
        return $this->loadUserByUsername($user->getUsername());
    }

    final public function supportsClass($class): bool
    {
        return is_a($class, Person::class);
    }
}
