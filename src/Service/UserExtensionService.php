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

namespace App\Service;

use App\Entity\Organization;
use App\Entity\Person;
use App\Security\OrganizationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserExtensionService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    public function __construct(
        EntityManagerInterface $em,
        SessionInterface $session,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->em = $em;
        $this->session = $session;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @return Organization|null|object
     */
    public function getCurrentOrganization()
    {
        if ($this->session->has('organization_id')) {
            return $this->em->getRepository('App:Organization')->find($this->session->get('organization_id'));
        }
        return null;
    }

    public function checkCurrentOrganization(UserInterface $user)
    {
        if (!$user instanceof Person) {
            return false;
        }

        if ($user->isGlobalAdministrator()) {
            return true;
        }

        return $this->session->has('organization_id')
            && (is_array($this->em->getRepository('App:Organization')->getMembershipByPersonQueryBuilder($user)
                ->andWhere('o = :organization')
                ->setParameter('organization', $this->getCurrentOrganization())
                ->getQuery()
                ->getResult()) || $this->em->getRepository('App:Organization')->getMembershipByPersonQueryBuilder($user)
                ->andWhere('o = :organization')
                ->setParameter('organization', $this->getCurrentOrganization())
                ->getQuery()
                ->getResult() instanceof \Countable ? count($this->em->getRepository('App:Organization')->getMembershipByPersonQueryBuilder($user)
                ->andWhere('o = :organization')
                ->setParameter('organization', $this->getCurrentOrganization())
                ->getQuery()
                ->getResult()) : 0) > 0;
    }

    public function isUserGlobalAdministrator()
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN');
    }

    public function isUserLocalAdministrator()
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN')
            || $this->authorizationChecker->isGranted(OrganizationVoter::MANAGE, $this->getCurrentOrganization());
    }
}
