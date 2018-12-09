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

use AppBundle\Entity\Membership;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Repository\Edu\TeachingRepository;
use AppBundle\Repository\Edu\TrainingRepository;
use AppBundle\Repository\RoleRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OrganizationVoter extends Voter
{
    const MANAGE = 'ORGANIZATION_MANAGE';
    const ACCESS = 'ORGANIZATION_ACCESS';
    const ACCESS_TRAININGS = 'ORGANIZATION_ACCESS_TRAININGS';
    const ACCESS_WORK_LINKED_TRAINING = 'ORGANIZATION_ACCESS_WORKLINKED_TRAINING';
    const MANAGE_WORK_LINKED_TRAINING = 'ORGANIZATION_MANAGE_WORKLINKED_TRAINING';
    const MANAGE_COMPANIES = 'ORGANIZATION_MANAGE_COMPANIES';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var TrainingRepository */
    private $trainingRepository;

    /** @var TeachingRepository */
    private $teachingRepository;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        RoleRepository $roleRepository,
        TrainingRepository $trainingRepository,
        TeachingRepository $teachingRepository
    ) {
        $this->decisionManager = $decisionManager;
        $this->roleRepository = $roleRepository;
        $this->trainingRepository = $trainingRepository;
        $this->teachingRepository = $teachingRepository;
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
            self::ACCESS_TRAININGS,
            self::ACCESS_WORK_LINKED_TRAINING,
            self::MANAGE_WORK_LINKED_TRAINING,
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
        if ($this->roleRepository->personHasRole($subject, $user->getPerson(), Role::ROLE_LOCAL_ADMIN)) {
            return true;
        }

        switch ($attribute) {
            // acceder a las enseñanzas del centro y a la gestión de empresas
            case self::MANAGE_COMPANIES:
            case self::ACCESS_TRAININGS:
                // Si es jefe de algún departamento o coordinador de FP dual, permitir acceder
                // 1) Jefe de departamento
                if ($this->trainingRepository->countByAcademicYearAndDepartmentHead(
                    $subject->getCurrentAcademicYear(),
                    $user->getPerson()
                ) > 0) {
                    return true;
                }

                // 2) Coordinador de FP dual
                if ($this->roleRepository->personHasRole($subject, $user->getPerson(), Role::ROLE_WLT_MANAGER)) {
                    return true;
                }
                break;

            case self::MANAGE_WORK_LINKED_TRAINING:
                return $this->roleRepository->personHasRole($subject, $user->getPerson(), Role::ROLE_WLT_MANAGER);

            case self::ACCESS_WORK_LINKED_TRAINING:
                // pueden acceder los que gestionan la FP dual, los profesores que imparten en dual, los gerentes
                // de los centros de trabajo con acuerdo de colaboración
                if ($this->roleRepository->personHasRole($subject, $user->getPerson(), Role::ROLE_WLT_MANAGER)) {
                    return true;
                }
                if ($this->teachingRepository->countAcademicYearAndPersonAndWLT(
                    $subject->getCurrentAcademicYear(),
                    $user->getPerson()
                ) > 0) {
                    return true;
                }
                return false;
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
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
