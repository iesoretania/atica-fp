<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Repository\ItpModule\TeacherRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class TrainingProgramVoter extends CachedVoter
{
    public const MANAGE = 'ITP_TRAINING_PROGRAM_MANAGE';
    public const MANAGE_PERMISSIONS = 'ITP_TRAINING_PROGRAM_MANAGE_GRADES';
    public const ACCESS = 'ITP_TRAINING_PROGRAM_ACCESS';

    public function __construct(
        CacheItemPoolInterface                          $cacheItemPoolItemPool,
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly TeacherRepository              $itpTeacherRepository,
        private readonly UserExtensionService           $userExtensionService
    ) {
        parent::__construct($cacheItemPoolItemPool);
    }

    /**
     * {@inheritdoc}
     */
    final public function supports($attribute, $subject): bool
    {
        if (!$subject instanceof TrainingProgram) {
            return false;
        }
        return in_array($attribute, [
            self::MANAGE_PERMISSIONS,
            self::MANAGE,
            self::ACCESS
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof TrainingProgram) {
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
        $isDepartmentHead = false;
        foreach ($subject->getTrainingProgramGrades() as $programGrade) {
            if ($programGrade->getGrade()?->getTraining()?->getAcademicYear()?->getOrganization() !== $organization) {
                return false;
            }
            if ($programGrade->getGrade()?->getTraining()?->getDepartment()?->getHead()?->getPerson() === $user) {
                $isDepartmentHead = true;
            }
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $academicYear = $organization->getCurrentAcademicYear();
        $teacher = $this->itpTeacherRepository->findOneByAcademicYearAndPerson($academicYear, $user);

        if ($teacher instanceof Teacher && $subject->getId() !== null) {
            // Si es tutor/a dual de algún grupo, también puede
            $groupManagers = $this->itpTeacherRepository->findProgramGroupManagersByTrainingProgram($subject);
            $isGroupManager = in_array($teacher, $groupManagers, true);
        } else {
            $isGroupManager = false;
        }

        switch ($attribute) {
            case self::MANAGE_PERMISSIONS:
                return $isDepartmentHead;
            case self::MANAGE:
            case self::ACCESS:
                return $isDepartmentHead || $isGroupManager;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
