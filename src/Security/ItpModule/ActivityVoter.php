<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Security\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class ActivityVoter extends CachedVoter
{
    public const MANAGE = 'ITP_ACTIVITY_MANAGE';

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly UserExtensionService $userExtensionService
    ) {
        parent::__construct($cacheItemPoolItemPool);
    }

    /**
     * {@inheritdoc}
     */
    final public function supports($attribute, $subject): bool
    {
        if (!$subject instanceof Activity) {
            return false;
        }
        return in_array($attribute, [
            self::MANAGE
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Activity) {
            return false;
        }

        /** @var Person $user */
        $user = $token->getUser();

        if (!$user instanceof Person) {
            // si el usuario no ha entrado, denegar
            return false;
        }

        $organization = $this->userExtensionService->getCurrentOrganization();

        // si el módulo está deshabilitado, denegar
        if (!$organization->getCurrentAcademicYear() instanceof AcademicYear ||
            !$organization->getCurrentAcademicYear()->hasModule('itp')) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        // Si la enseñanza no es de la organización actual, denegar
        if (
            !$subject->getProgramGrade() instanceof ProgramGrade ||
            !$subject->getProgramGrade()->getTrainingProgram() instanceof TrainingProgram ||
            !$subject->getProgramGrade()->getTrainingProgram()->getTraining() instanceof Training ||
            !$subject->getProgramGrade()->getTrainingProgram()->getTraining()->getAcademicYear() instanceof AcademicYear ||
            $subject->getProgramGrade()->getTrainingProgram()->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $isCurrentAcademicYear = $subject->getProgramGrade()->getTrainingProgram()->getTraining()->getAcademicYear()
            === $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();

        // El jefe de departamento de la familia profesional de proyecto también puede
        $isDepartmentHead = $subject->getProgramGrade()->getTrainingProgram()->getTraining()->getDepartment()
            && $subject->getProgramGrade()->getTrainingProgram()->getTraining()->getDepartment()->getHead()
            && $subject->getProgramGrade()->getTrainingProgram()->getTraining()->getDepartment()->getHead()->getPerson() === $user;

        switch ($attribute) {
            case self::MANAGE:
                return $isDepartmentHead;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
