<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\RoleRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TrainingVoter extends Voter
{
    const MANAGE = 'TRAINING_MANAGE';
    const ACCESS = 'TRAINING_ACCESS';

    /** @var UserExtensionService $userExtensionService */
    private $userExtensionService;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var TeacherRepository */
    private $teacherRepository;

    public function __construct(
        UserExtensionService $userExtensionService,
        RoleRepository $roleRepository,
        TeacherRepository $teacherRepository
    ) {
        $this->userExtensionService = $userExtensionService;
        $this->roleRepository = $roleRepository;
        $this->teacherRepository = $teacherRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Training) {
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
        if (!$subject instanceof Training) {
            return false;
        }

        // si la enseñanza no pertenece a la organización actual, denegar
        $organization = $this->userExtensionService->getCurrentOrganization();
        if ($subject->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->userExtensionService->isUserGlobalAdministrator()) {
            return true;
        }

        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            // si el usuario no ha entrado, denegar
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->roleRepository->personHasRole($organization, $user->getPerson(), Role::ROLE_LOCAL_ADMIN)) {
            return true;
        }

        // Si es el coordinador de FP dual, permitir si el ciclo es dual
        if ($subject->isWorkLinked() && $this->roleRepository->personHasRole($organization, $user->getPerson(), Role::ROLE_WLT_MANAGER)) {
            return true;
        }

        // Si es el jefe de departamento de la enseñanza, permitir siempre
        if ($subject->getDepartment() && $subject->getDepartment()->getHead()->getPerson() === $user->getPerson()) {
            return true;
        }

        // Si es permiso de acceso, comprobar que es un profesor de ese curso académico
        if ($attribute === self::ACCESS) {
            return null !== $this->teacherRepository->findOneByPersonAndAcademicYear($user->getPerson(), $subject->getAcademicYear());
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
