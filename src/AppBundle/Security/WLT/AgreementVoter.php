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

use AppBundle\Entity\User;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Security\CachedVoter;
use AppBundle\Security\Edu\GroupVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AgreementVoter extends CachedVoter
{
    const MANAGE = 'WLT_AGREEMENT_MANAGE';
    const ACCESS = 'WLT_AGREEMENT_ACCESS';
    const ATTENDANCE = 'WLT_AGREEMENT_ATTENDANCE';
    const LOCK = 'WLT_AGREEMENT_LOCK';
    const GRADE = 'WLT_AGREEMENT_GRADE';
    const VIEW_GRADE = 'WLT_AGREEMENT_VIEW_GRADE';
    const VIEW_STUDENT_SURVEY = 'WLT_AGREEMENT_VIEW_STUDENT_SURVEY';
    const FILL_STUDENT_SURVEY = 'WLT_AGREEMENT_FILL_STUDENT_SURVEY';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var UserExtensionService */
    private $userExtensionService;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        UserExtensionService $userExtensionService
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
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
            self::LOCK,
            self::GRADE,
            self::VIEW_GRADE,
            self::VIEW_STUDENT_SURVEY,
            self::FILL_STUDENT_SURVEY
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
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
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
        if ($this->decisionManager->decide($token, [OrganizationVoter::WLT_MANAGER], $organization)) {
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
                return $isStudent || $isTeacher || $isWorkTutor || $isGroupTutor;

            // Si es permiso para ver la evaluación, el profesorado del grupo, el tutor o el responsable laboral
            case self::VIEW_GRADE:
                return $isTeacher || $isWorkTutor || $isGroupTutor;

            // Si es permiso para pasar lista o evaluar, el tutor de grupo o el responsable laboral
            case self::ATTENDANCE:
            case self::GRADE:
                return $isWorkTutor || $isGroupTutor;

            // Si es permiso para bloquear/desbloquear jornadas, el tutor de grupo
            case self::LOCK:
                return $isGroupTutor;

            case self::VIEW_STUDENT_SURVEY:
            case self::FILL_STUDENT_SURVEY:
                return $isStudent || $isGroupTutor;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
