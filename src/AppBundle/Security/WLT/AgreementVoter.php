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

namespace AppBundle\Security\WLT;

use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Repository\Edu\TeachingRepository;
use AppBundle\Repository\RoleRepository;
use AppBundle\Security\Edu\GroupVoter;
use AppBundle\Service\UserExtensionService;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class AgreementVoter extends Voter
{
    const MANAGE = 'WLT_AGREEMENT_MANAGE';
    const ACCESS = 'WLT_AGREEMENT_ACCESS';
    const ATTENDANCE = 'WLT_AGREEMENT_ATTENDANCE';
    const LOCK = 'WLT_AGREEMENT_LOCK';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var UserExtensionService */
    private $userExtensionService;

    /** @var TeachingRepository */
    private $teachingRepository;

    public function __construct(
        AccessDecisionManagerInterface $decisionManager,
        RoleRepository $roleRepository,
        TeachingRepository $teachingRepository,
        UserExtensionService $userExtensionService
    ) {
        $this->decisionManager = $decisionManager;
        $this->roleRepository = $roleRepository;
        $this->teachingRepository = $teachingRepository;
        $this->userExtensionService = $userExtensionService;
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
            self::ATTENDANCE,
            self::LOCK
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

        $organization = $this->userExtensionService->getCurrentOrganization();

        // Si es administrador de la organización, permitir siempre
        if ($this->roleRepository->personHasRole($organization, $user->getPerson(), Role::ROLE_LOCAL_ADMIN)) {
            return true;
        }

        // Si es jefe de su departamento o coordinador de FP dual, permitir acceder siempre

        // Jefe del departamento del estudiante
        if (null !== $subject->getStudentEnrollment()) {
            $training = $subject->getStudentEnrollment()->getGroup()->getGrade()->getTraining();
            if (null !== $training->getDepartment() && $training->getDepartment()->getHead() &&
                $training->getDepartment()->getHead()->getPerson() === $user->getPerson()
            ) {
                return true;
            }
        }

        // Coordinador de FP dual
        if ($this->roleRepository->personHasRole($organization, $user->getPerson(), Role::ROLE_WLT_MANAGER)) {
            return true;
        }

        // Otros casos: ver qué permisos tiene el usuario

        // Tutor laboral
        $isWorkTutor = $user === $subject->getWorkTutor()->getUser();

        $isGroupTutor = false;

        // Tutor del grupo del acuerdo
        $tutors = $subject->getStudentEnrollment()->getGroup()->getTutors();
        foreach ($tutors as $tutor) {
            if ($tutor->getPerson()->getUser() === $user) {
                $isGroupTutor = true;
                break;
            }
        }

        // Estudiante del acuerdo
        $isStudent = $user === $subject->getStudentEnrollment()->getPerson()->getUser();

        // Docente del grupo del acuerdo
        $isTeacher = $this->decisionManager->decide(
            $token,
            [GroupVoter::TEACH],
            $subject->getStudentEnrollment()->getGroup()
        );

        switch ($attribute) {
            // Si es permiso de acceso, comprobar si es el estudiante, docente, el tutor de grupo o
            // el responsable laboral
            case self::ACCESS:
                return $isTeacher || $isStudent || $isWorkTutor || $isGroupTutor;

            // Si es permiso para pasar lista, el tutor de grupo o el responsable laboral
            case self::ATTENDANCE:
                return $isWorkTutor || $isGroupTutor;

            // Si es permiso para bloquear/desbloquear jornadas, el tutor de grupo
            case self::LOCK:
                return $isGroupTutor;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
