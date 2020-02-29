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

namespace AppBundle\Security\WPT;

use AppBundle\Entity\Organization;
use AppBundle\Entity\User;
use AppBundle\Security\CachedVoter;
use AppBundle\Security\OrganizationVoter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class WPTOrganizationVoter extends CachedVoter
{
    const WPT_ACCESS = 'ORGANIZATION_ACCESS_WORKPLACE_TRAINING';
    const WPT_MANAGE = 'ORGANIZATION_MANAGE_WORKPLACE_TRAINING';

    const WPT_GROUP_TUTOR = 'ORGANIZATION_WPT_GROUP_TUTOR';
    const WPT_WORK_TUTOR = 'ORGANIZATION_WPT_WORK_TUTOR';
    const WPT_STUDENT = 'ORGANIZATION_WPT_STUDENT';
    const WPT_EDUCATIONAL_TUTOR = 'ORGANIZATION_WPT_EDUCATIONAL_TUTOR';
    const WPT_DEPARTMENT_HEAD = 'ORGANIZATION_WPT_DEPARTMENT_HEAD';

    private $decisionManager;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
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
            self::WPT_ACCESS,
            self::WPT_MANAGE,
            self::WPT_WORK_TUTOR,
            self::WPT_GROUP_TUTOR,
            self::WPT_STUDENT,
            self::WPT_EDUCATIONAL_TUTOR,
            self::WPT_DEPARTMENT_HEAD
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
        if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)
        ) {
            return true;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
