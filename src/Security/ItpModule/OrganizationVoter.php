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

namespace App\Security\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Person;
use App\Security\CachedVoter;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter as BaseOrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class OrganizationVoter extends CachedVoter
{
    public const ITP_ACCESS_SECTION = 'ORGANIZATION_ACCESS_IN_COMPANY_TRAINING_PHASE';
    public const ITP_MANAGER = 'ORGANIZATION_MANAGE_IN_COMPANY_TRAINING_PHASE';

    public function __construct(
        CacheItemPoolInterface                          $cacheItemPoolItemPool,
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly UserExtensionService           $userExtensionService
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
            self::ITP_ACCESS_SECTION,
            self::ITP_MANAGER,
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

        /** @var Person $user */
        $user = $token->getUser();

        if (!$user instanceof Person) {
            // si el usuario no ha entrado, denegar
            return false;
        }

        // si el módulo está deshabilitado, denegar
        if (!$this->userExtensionService->getCurrentOrganization() instanceof Organization ||
            !$this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear() instanceof AcademicYear ||
            !$this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear()->hasModule('itp')) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [BaseOrganizationVoter::LOCAL_MANAGE], $subject)
        ) {
            return true;
        }
        switch ($attribute) {
            case self::ITP_MANAGER:
                // Si es jefe de algún departamento, permitir acceder
                // Jefe de departamento
                return $this->decisionManager->decide($token, [EduOrganizationVoter::EDU_DEPARTMENT_HEAD], $subject);
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
