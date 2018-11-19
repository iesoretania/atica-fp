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

namespace AppBundle\Security\Edu;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\User;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AcademicYearVoter extends Voter
{
    const MANAGE = 'ACADEMIC_YEAR_MANAGE';
    const ACCESS = 'ACADEMIC_YEAR_ACCESS';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /**
     * @var ManagerRegistry
     */
    private $managerRegistry;

    public function __construct(AccessDecisionManagerInterface $decisionManager, ManagerRegistry $managerRegistry)
    {
        $this->decisionManager = $decisionManager;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof AcademicYear) {
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
        if (!$subject instanceof AcademicYear) {
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
        if ($user->getManagedOrganizations()->contains($subject->getOrganization())) {
            return true;
        }

        // Si es permiso de acceso, comprobar que es un profesor de ese curso académico
        if ($attribute === self::ACCESS) {
            return (null !== $this->managerRegistry->getRepository(Teacher::class)->findBy([
                'person' => $user->getPerson(),
                'academicYear' => $subject
            ]));
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
