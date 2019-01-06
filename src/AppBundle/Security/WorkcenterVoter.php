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

namespace AppBundle\Security;

use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Entity\Workcenter;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\Edu\TrainingRepository;
use AppBundle\Repository\RoleRepository;
use AppBundle\Service\UserExtensionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class WorkcenterVoter extends Voter
{
    const MANAGE = 'WORKCENTER_MANAGE';
    const ACCESS = 'WORKCENTER_ACCESS';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var UserExtensionService $userExtensionService */
    private $userExtensionService;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var TeacherRepository */
    private $teacherRepository;

    /** @var TrainingRepository */
    private $trainingRepository;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        UserExtensionService $userExtensionService,
        RoleRepository $roleRepository,
        TeacherRepository $teacherRepository,
        TrainingRepository $trainingRepository
    ) {
        $this->decisionManager = $decisionManager;
        $this->userExtensionService = $userExtensionService;
        $this->roleRepository = $roleRepository;
        $this->teacherRepository = $teacherRepository;
        $this->trainingRepository = $trainingRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {
        if (!$subject instanceof Workcenter) {
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
        if (!$subject instanceof Workcenter) {
            return false;
        }

        // si el curso académico no pertenece a la organización actual, denegar
        $organization = $this->userExtensionService->getCurrentOrganization();
        if ($subject->getAcademicYear()->getOrganization() !== $organization) {
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
        if ($this->roleRepository->personHasRole($organization, $user->getPerson(), Role::ROLE_LOCAL_ADMIN)) {
            return true;
        }

        switch ($attribute) {
            // Si es permiso de acceso, comprobar que es un profesor de ese curso académico
            case self::ACCESS:
                return null !== $this->teacherRepository
                        ->findOneByPersonAndAcademicYear($user->getPerson(), $subject->getAcademicYear());

            // Si es jefe de algún departamento de FP o el coordinador de FP dual, permitir gestionar
            case self::MANAGE:
                // 1) Jefe de departamento
                if ($this->trainingRepository->countAcademicYearAndDepartmentHead(
                        $subject->getAcademicYear(),
                        $user->getPerson()
                    ) > 0) {
                    return true;
                }

                // 2) Coordinador de FP dual
                if ($this->roleRepository->personHasRole($subject, $user->getPerson(), Role::ROLE_WLT_MANAGER)) {
                    return true;
                }
                break;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
