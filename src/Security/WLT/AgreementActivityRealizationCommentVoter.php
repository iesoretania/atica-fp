<?php
/*
  Copyright (C) 2018-2022: Luis Ramón López López

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

namespace App\Security\WLT;

use App\Entity\Person;
use App\Entity\WLT\AgreementActivityRealizationComment;
use App\Security\CachedVoter;
use App\Security\Edu\GroupVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AgreementActivityRealizationCommentVoter extends CachedVoter
{
    public const DELETE = 'WLT_AGREEMENT_ACTIVITY_REALIZATION_COMMENT_DELETE';
    public const ACCESS = 'WLT_AGREEMENT_ACTIVITY_REALIZATION_COMMENT_ACCESS';

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

        if (!$subject instanceof AgreementActivityRealizationComment) {
            return false;
        }
        return in_array($attribute, [
            self::DELETE,
            self::ACCESS
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof AgreementActivityRealizationComment) {
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

        $organization = $this->userExtensionService->getCurrentOrganization();

        // si es de otra organización, denegar
        if ($organization !== $this->userExtensionService->getCurrentOrganization()) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $person = $user;

        $agreement = $subject->getAgreementActivityRealization()->getAgreement();

        $isDepartmentHead = false;
        $isWltManager = false;
        $isGroupTutor = false;
        $isTeacher = false;
        $academicYearIsCurrent = false;

        // Coordinador de FP dual, autorizado salvo modificar si el acuerdo es de otro curso académico
        if ($agreement->getProject()->getManager() === $person) {
            $isWltManager = true;
        }

        // Tutor laboral y de seguimiento
        $isWorkTutor = ($user === $agreement->getWorkTutor() || $user === $agreement->getAdditionalWorkTutor());
        $isEducationalTutor =
            ($agreement->getEducationalTutor() && $agreement->getEducationalTutor()->getPerson() === $person) ||
            (
                $agreement->getAdditionalEducationalTutor() &&
                $agreement->getAdditionalEducationalTutor()->getPerson() === $person
            );


        // hay estudiante asociado (puede que no lo haya si es un convenio nuevo)
        if ($agreement->getStudentEnrollment()) {
            // Si es jefe de su departamento o coordinador de FP dual, permitir acceder siempre
            // Jefe del departamento del estudiante, autorizado salvo modificar si el acuerdo es de otro curso académico
            $training = $agreement->getStudentEnrollment()->getGroup()->getGrade()->getTraining();
            if ($training && $training->getDepartment() && $training->getDepartment()->getHead() &&
                $training->getDepartment()->getHead()->getPerson() === $person) {
                $isDepartmentHead = true;
            }

            // Otros casos: ver qué permisos tiene el usuario

            // Tutor del grupo del acuerdo
            $tutors = $agreement->getStudentEnrollment()->getGroup()->getTutors();
            foreach ($tutors as $tutor) {
                if ($tutor->getPerson() === $user) {
                    $isGroupTutor = true;
                    break;
                }
            }

            // Docente del grupo del acuerdo
            $isTeacher = $this->decisionManager->decide(
                $token,
                [GroupVoter::TEACH],
                $agreement->getStudentEnrollment()->getGroup()
            );

            $academicYearIsCurrent = $organization->getCurrentAcademicYear() === $training->getAcademicYear();
        }

        switch ($attribute) {
            case self::DELETE:
                // Pueden borrar todos los comentarios el jefe/a de departamento, el coordinador del proyecto,
                // el responsable de seguimiento o el autor original del comentario
                if ($isDepartmentHead || $isWltManager || $isEducationalTutor || $subject->getPerson() === $person) {
                    return $academicYearIsCurrent;
                }
                return false;

            // Si es permiso de acceso, comprobar si es docente del grupo, el tutor de grupo o
            // el responsable de seguimiento o laboral
            case self::ACCESS:
                return $isDepartmentHead || $isWltManager || $isEducationalTutor
                    || $isTeacher || $isWorkTutor || $isGroupTutor;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
