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

namespace App\Security\Edu;

use App\Entity\Edu\ReportTemplate;
use App\Entity\Person;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class ReportTemplateVoter extends CachedVoter
{
    public const EDU_REPORT_TEMPLATE_VIEW = 'EDU_REPORT_TEMPLATE_VIEW';
    public const EDU_REPORT_TEMPLATE_MANAGE = 'EDU_REPORT_TEMPLATE_MANAGE';

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly UserExtensionService $userExtensionService
    ) {
        parent::__construct($cacheItemPoolItemPool);
    }

    /**
     * {@inheritdoc}
     */
    final public function supports($attribute, $subject): bool
    {

        if (!$subject instanceof ReportTemplate) {
            return false;
        }
        return in_array($attribute, [
            self::EDU_REPORT_TEMPLATE_VIEW,
            self::EDU_REPORT_TEMPLATE_MANAGE,
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof ReportTemplate) {
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
        // Si es administrador de la organización actual, permitir siempre
        // denegamos en cualquier otro caso
        return $subject->getOrganization() === $this->userExtensionService->getCurrentOrganization()
            && $this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject->getOrganization());
    }
}
