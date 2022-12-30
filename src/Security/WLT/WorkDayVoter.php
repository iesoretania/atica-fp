<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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
use App\Entity\WLT\WorkDay;
use App\Security\CachedVoter;
use App\Security\Edu\GroupVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class WorkDayVoter extends CachedVoter
{
    public const FILL = 'WLT_WORK_DAY_MANAGE';
    public const ACCESS = 'WLT_WORK_DAY_ACCESS';

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

        if (!$subject instanceof WorkDay) {
            return false;
        }
        return in_array($attribute, [
            self::FILL,
            self::ACCESS
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof WorkDay) {
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

        // Si no es de la organización actual, denegar
        if ($subject->getAgreement()->getProject()->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $accessGranted = $this->decisionManager->decide($token, [AgreementVoter::ACCESS], $subject->getAgreement());

        $isStudent = $subject->getAgreement()->getStudentEnrollment()->getPerson() === $user;
        $isManager = $this->decisionManager->decide($token, [AgreementVoter::MANAGE], $subject->getAgreement());
        $isTutor = $subject->getAgreement()->getEducationalTutor()->getPerson() === $user
            || ($subject->getAgreement()->getAdditionalEducationalTutor()
                && $subject->getAgreement()->getAdditionalEducationalTutor()->getPerson() === $user)
            || $subject->getAgreement()->getWorkTutor() === $user
            || ($subject->getAgreement()->getAdditionalWorkTutor() === $user);
        $isGroupTutor = $this->decisionManager->decide($token, [GroupVoter::MANAGE],
            $subject->getAgreement()->getStudentEnrollment()->getGroup());

        switch ($attribute) {
            case self::ACCESS:
                // Si se puede acceder al convenio, se puede visualizar la jornada
                return $accessGranted;
            case self::FILL:
                // Sólo si pertenece al curso académico activo y es el estudiante,
                // algún tutor (docente, laboral o de grupo) o puede administrar el
                // convenio
                return $accessGranted
                    && $subject
                        ->getAgreement()
                        ->getStudentEnrollment()
                        ->getGroup()
                        ->getGrade()
                        ->getTraining()
                        ->getAcademicYear() === $organization->getCurrentAcademicYear()
                    && ($isManager || $isStudent || $isTutor || $isGroupTutor);
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
