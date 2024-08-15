<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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
use App\Repository\OrganizationRepository;
use App\Security\OrganizationVoter;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserExtensionService
{
    public function __construct(private readonly RequestStack                  $requestStack, private readonly AuthorizationCheckerInterface $authorizationChecker, private readonly OrganizationRepository        $organizationRepository)
    {
    }

    final public function getCurrentOrganization(): ?Organization
    {
        if ($this->requestStack->getSession()->has('organization_id')) {
            return $this->organizationRepository->find($this->requestStack->getSession()->get('organization_id'));
        }
        return null;
    }

    final public function checkCurrentOrganization(UserInterface $user): bool
    {
        if (!$user instanceof Person) {
            return false;
        }

        if ($user->isGlobalAdministrator()) {
            return true;
        }

        return $this->requestStack->getSession()->has('organization_id')
            && (is_countable($this->organizationRepository->getMembershipByPersonQueryBuilder($user)
                ->andWhere('o = :organization')
                ->setParameter('organization', $this->getCurrentOrganization())
                ->getQuery()
                ->getResult()) || $this->organizationRepository->getMembershipByPersonQueryBuilder($user)
                ->andWhere('o = :organization')
                ->setParameter('organization', $this->getCurrentOrganization())
                ->getQuery()
                ->getResult());
    }

    final public function isUserGlobalAdministrator(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN');
    }

    final public function isUserLocalAdministrator(): bool
    {
        return $this->authorizationChecker->isGranted('ROLE_ADMIN')
            || $this->authorizationChecker->isGranted(OrganizationVoter::MANAGE, $this->getCurrentOrganization());
    }

    final public function getOrganizations(?Person $user)
    {
        if (!$user instanceof Person) {
            return [];
        }

        return $this->organizationRepository->getMembershipByPerson($user);
    }
}
