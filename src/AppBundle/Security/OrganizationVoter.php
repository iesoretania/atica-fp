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

namespace AppBundle\Security;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Repository\RoleRepository;
use AppBundle\Security\Edu\EduOrganizationVoter;
use AppBundle\Security\WLT\WLTOrganizationVoter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class OrganizationVoter extends CachedVoter
{
    const MANAGE = 'ORGANIZATION_MANAGE';
    const ACCESS = 'ORGANIZATION_ACCESS';
    const ACCESS_SECTION = 'ORGANIZATION_ACCESS_SECTION';
    const LOCAL_MANAGE = 'ORGANIZATION_LOCAL_MANAGE';
    const MANAGE_COMPANIES = 'ORGANIZATION_MANAGE_COMPANIES';

    private $decisionManager;
    private $roleRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        RoleRepository $roleRepository
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->roleRepository = $roleRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Organization) {
            return false;
        }

        if (!in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
            self::ACCESS_SECTION,
            self::LOCAL_MANAGE,
            self::MANAGE_COMPANIES
        ], true)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof Organization) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
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
                    personHasRole($subject, $user->getPerson(), Role::ROLE_LOCAL_ADMIN);

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
                return $this->decisionManager->decide($token, [WLTOrganizationVoter::WLT_MANAGER], $subject);

            case self::ACCESS:
                // Si es permiso de acceso, comprobar que pertenece actualmente a la organización
                if ($attribute === self::ACCESS) {
                    $date = new \DateTime();
                    /** @var Membership $membership */
                    foreach ($user->getMemberships() as $membership) {
                        if ($membership->getOrganization() === $subject && $membership->getValidFrom() <= $date &&
                            ($membership->getValidUntil() === null || $membership->getValidUntil() >= $date)) {
                            return true;
                        }
                    }
                }
                break;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
