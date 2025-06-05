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
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class TeacherVoter extends CachedVoter
{
    public const ACCESS_EXPENSE = 'ITP_ACCESS_EXPENSE';
    public const MANAGE_EXPENSE = 'ITP_CREATE_EXPENSE';

    public function __construct(
        CacheItemPoolInterface                              $cacheItemPoolItemPool,
        private readonly StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository,
        private readonly AccessDecisionManagerInterface     $decisionManager,
        private readonly UserExtensionService               $userExtensionService
    )
    {
        parent::__construct($cacheItemPoolItemPool);
    }

    /**
     * {@inheritdoc}
     */
    final public function supports($attribute, $subject): bool
    {

        if (!$subject instanceof Teacher) {
            return false;
        }
        return in_array($attribute, [
            self::ACCESS_EXPENSE,
            self::MANAGE_EXPENSE
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Teacher) {
            return false;
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

        // si el módulo está deshabilitado, denegar
        if (!$organization->getCurrentAcademicYear() instanceof AcademicYear ||
            !$organization->getCurrentAcademicYear()->hasModule('itp')) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        return match ($attribute) {
            self::ACCESS_EXPENSE => $this->checkCreateExpensePermission($subject, $user, false),
            self::MANAGE_EXPENSE => $this->checkCreateExpensePermission($subject, $user, true),
            // denegamos en cualquier otro caso
            default => false,
        };
    }

    private function checkCreateExpensePermission(Teacher $subject, Person $user, bool $readOnly): bool
    {
        // El propio docente puede registrar sus dietas
        if ($subject->getPerson() === $user) {
            return true;
        }

        $trainingPrograms = $this->studentProgramWorkcenterRepository->findRelatedTrainingProgramsByAcademicYearPersonManagerAndQuery(
            $subject->getAcademicYear(), $subject->getPerson(), false, null);

        foreach ($trainingPrograms as $trainingProgram) {
            assert($trainingProgram instanceof TrainingProgram);
            $programGrades = $trainingProgram->getTrainingProgramGrades();
            foreach ($programGrades as $programGrade) {
                assert($programGrade instanceof ProgramGrade);
                if ($programGrade->getGrade()->getTraining()?->getDepartment()->getHead()->getPerson() === $user) {
                    return true;
                }
                $programGroups = $programGrade->getTrainingProgramGroups();
                foreach ($programGroups as $programGroup) {
                    assert($programGroup instanceof ProgramGroup);
                    $managers = $programGroup->getManagers()->toArray();

                    if (!$readOnly) {
                        $tutors = $programGroup->getGroup()->getTutors()->toArray();
                        // Fusionar los tutores y los gestores
                        $managers = array_merge($tutors, $managers);
                    }
                    foreach ($managers as $manager) {
                        assert($manager instanceof Teacher);
                        if ($manager->getPerson() === $user) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }
}
