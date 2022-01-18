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

namespace App\Security\WPT;

use App\Entity\Survey;
use App\Entity\User;
use App\Entity\WPT\Agreement;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AgreementVoter extends CachedVoter
{
    const MANAGE = 'WPT_AGREEMENT_MANAGE';
    const ACCESS = 'WPT_AGREEMENT_ACCESS';

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
            self::ACCESS
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

        // si es de otra organización, denegar
        if ($organization !== $this->userExtensionService->getCurrentOrganization()) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $person = $user->getPerson();

        $academicYearIsCurrent = $subject->getShift()->getGrade()
            && $subject->getShift()->getGrade()->getTraining()->getAcademicYear()
            === $organization->getCurrentAcademicYear();

        // es jefe de departamento de la enseñanza de la convocatoria
        $isDepartmentHead = $subject->getShift()->getGrade()
            && $subject->getShift()->getGrade()->getTraining()->getDepartment()
            && $subject->getShift()->getGrade()->getTraining()->getDepartment()->getHead()
            && $subject->getShift()->getGrade()->getTraining()->getDepartment()->getHead()->getPerson() === $person;

        switch ($attribute) {
            case self::MANAGE:
                if ($isDepartmentHead) {
                    return $academicYearIsCurrent;
                }
                return false;

            // Si es permiso de acceso, comprobar si es el estudiante, docente, el tutor de grupo o
            // el responsable laboral
            case self::ACCESS:
                return $isDepartmentHead;
        }

        // denegamos en cualquier otro caso
        return false;
    }

    /**
     * @param Survey $survey
     * @return bool
     * @throws \Exception
     */
    private function checkSurvey(Survey $survey)
    {
        $now = new \DateTime();

        if (!$survey) {
            return false;
        }
        if ($survey->getStartTimestamp() && $survey->getStartTimestamp() > $now) {
            return false;
        }
        if ($survey->getEndTimestamp() && $survey->getEndTimestamp() < $now) {
            return false;
        }
        return true;
    }
}
