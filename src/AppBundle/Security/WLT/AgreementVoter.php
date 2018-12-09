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

namespace AppBundle\Security\WLT;

use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Repository\RoleRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AgreementVoter extends Voter
{
    const MANAGE = 'WLT_AGREEMENT_MANAGE';
    const ACCESS = 'WLT_AGREEMENT_ACCESS';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var RoleRepository */
    private $roleRepository;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        RoleRepository $roleRepository
    ) {
        $this->decisionManager = $decisionManager;
        $this->roleRepository = $roleRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Agreement) {
            return false;
        }

        if (!in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
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
        if (!$subject instanceof Agreement) {
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
        if ($this->roleRepository->personHasRole($subject, $user->getPerson(), Role::ROLE_LOCAL_ADMIN)) {
            return true;
        }

        // Si es jefe de su departamento o coordinador de FP dual, permitir acceder
        // 1) Jefe del departamento del estudiante
        $training = $subject->getStudentEnrollment()->getGroup()->getGrade()->getTraining();
        if (null !== $training->getDepartment() && $training->getDepartment()->getHead() &&
            $training->getDepartment()->getHead()->getPerson() === $user
        ) {
            return true;
        }

        // 2) Coordinador de FP dual
        if ($this->roleRepository->personHasRole($subject, $user->getPerson(), Role::ROLE_WLT_MANAGER)) {
            return true;
        }

        // Si es permiso de acceso, comprobar si es el estudiante, el tutor de grupo o el responsable laboral
        if ($attribute === self::ACCESS) {
            // estudiante
            if ($user === $subject->getStudentEnrollment()->getPerson()->getUser()) {
                return true;
            }

            // responsable laboral
            if ($user === $subject->getWorkTutor()->getUser()) {
                return true;
            }

            // tutores de grupo
            $tutors = $subject->getStudentEnrollment()->getGroup()->getTutors();
            foreach ($tutors as $tutor) {
                if ($tutor->getPerson() === $user) {
                    return true;
                }
            }
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
