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

namespace App\Security;

use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Role;
use App\Repository\OrganizationRepository;
use App\Repository\RoleRepository;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class OrganizationVoter extends CachedVoter
{
    public const MANAGE = 'ORGANIZATION_MANAGE';
    public const ACCESS = 'ORGANIZATION_ACCESS';
    public const ACCESS_SECTION = 'ORGANIZATION_ACCESS_SECTION';
    public const LOCAL_MANAGE = 'ORGANIZATION_LOCAL_MANAGE';
    public const MANAGE_COMPANIES = 'ORGANIZATION_MANAGE_COMPANIES';

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly OrganizationRepository $organizationRepository,
        private readonly RoleRepository $roleRepository
    ) {
        parent::__construct($cacheItemPoolItemPool);
    }

    /**
     * {@inheritdoc}
     */
    final public function supports($attribute, $subject): bool
    {

        if (!$subject instanceof Organization) {
            return false;
        }
        return in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
            self::ACCESS_SECTION,
            self::LOCAL_MANAGE,
            self::MANAGE_COMPANIES
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Organization) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        /** @var Person $user */
        $user = $token->getUser();

        if (!$user instanceof Person) {
            // si el usuario no ha entrado, denegar
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($attribute !== self::LOCAL_MANAGE && $this->decisionManager->decide($token, [self::LOCAL_MANAGE], $subject)
            ) {
            return true;
        }

        switch ($attribute) {
            case self::LOCAL_MANAGE:
                return $this->roleRepository->
                    personHasRole($subject, $user, Role::ROLE_LOCAL_ADMIN);

            case self::ACCESS_SECTION:
                return $this->decisionManager->decide($token, [self::LOCAL_MANAGE], $subject) ||
                    ($this->decisionManager->decide($token, [EduOrganizationVoter::EDU_FINANCIAL_MANAGER], $subject)
                );

            // acceder a las enseñanzas del centro y a la gestión de empresas
            case self::MANAGE_COMPANIES:
                // Si es jefe de algún departamento o coordinador de FP dual, permitir acceder
                // 1) Jefe de departamento
                if ($this->decisionManager->decide($token, [EduOrganizationVoter::EDU_DEPARTMENT_HEAD], $subject)) {
                    return true;
                }

                // 2) Coordinador de FP dual
                return $this->decisionManager->decide($token, [WltOrganizationVoter::WLT_MANAGER], $subject);

            case self::ACCESS:
                // Si es permiso de acceso, comprobar que pertenece actualmente a la organización
                if ($attribute === self::ACCESS) {
                    return $this->organizationRepository->findByUserAndOrganization($user, $subject);
                }
                break;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
