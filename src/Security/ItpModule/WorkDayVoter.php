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
use App\Entity\ItpModule\WorkDay;
use App\Entity\Person;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class WorkDayVoter extends CachedVoter
{
    public const FILL = 'ITP_WORK_DAY_FILL';
    public const ACCESS = 'ITP_WORK_DAY_ACCESS';

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
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof WorkDay) {
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
        $training = $subject->getStudentProgramWorkcenter()?->getStudentProgram()?->getProgramGroup()?->getProgramGrade()?->getGrade()?->getTraining();
        $academicYear = $training?->getAcademicYear();
        if ($academicYear?->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $isCurrentAcademicYear = $academicYear
            === $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();

        // El jefe de departamento de la familia profesional de proyecto también puede
        $isDepartmentHead = $training?->getDepartment()?->getHead()?->getPerson() === $user;

        $accessGranted = /*$isStudent || $isGroupTutor || $isTutor || $isManager || */$isDepartmentHead;

        return match ($attribute) {
            // Si se puede acceder al convenio, se puede visualizar la jornada
            self::ACCESS => $accessGranted,
            // Solo si pertenece al curso académico activo y es el estudiante,
            // algún tutor (docente, laboral o de grupo) o puede administrar el
            // convenio
            self::FILL => $accessGranted
                && $academicYear === $organization->getCurrentAcademicYear()
                && (/*$isManager || $isStudent || $isTutor || $isGroupTutor || */$isDepartmentHead),
            // denegamos en cualquier otro caso
            default => false,
        };
    }
}
