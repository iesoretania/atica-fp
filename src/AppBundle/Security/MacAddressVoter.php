<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

use AppBundle\Entity\ICT\MacAddress;
use AppBundle\Entity\Membership;
use AppBundle\Entity\User;
use AppBundle\Service\UserExtensionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class MacAddressVoter extends Voter
{
    const MANAGE = 'MAC_ADDRESS_MANAGE';
    const ACCESS = 'MAC_ADDRESS_ACCESS';

    private $decisionManager;

    private $userExtensionService;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        UserExtensionService $userExtensionService
    ) {
        $this->decisionManager = $decisionManager;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof MacAddress) {
            return false;
        }

        if (!in_array($attribute, [self::MANAGE, self::ACCESS], true)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof MacAddress) {
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

        $organization = $subject->getOrganization();

        // Si no pertenece a la organización activa, denegar
        if ($this->userExtensionService->getCurrentOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($user->getManagedOrganizations()->contains($organization)) {
            return true;
        }

        // Comprobar que el dispositivo es suyo y que el usuario
        // pertenece actualmente a la organización
        $person = $user->getPerson();
        if ($subject->getPerson() === $person) {
            $date = new \DateTime();
            /** @var Membership $membership */
            foreach ($user->getMemberships() as $membership) {
                if ($membership->getOrganization() === $organization &&
                    $membership->getValidFrom() <= $date &&
                    ($membership->getValidUntil() === null || $membership->getValidUntil() >= $date)) {
                    // Permitir:
                    // - Si el permiso no es de gestión
                    return $attribute !== self::MANAGE;
                }
            }
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
