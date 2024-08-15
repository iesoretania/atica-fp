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

namespace App\Security\WLT;

use App\Entity\Edu\Group;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WLT\Project;
use App\Repository\WLT\ProjectRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class WLTTeacherVoter extends CachedVoter
{
    public const ACCESS_EDUCATIONAL_TUTOR_SURVEY = 'WLT_TEACHER_EDUCATIONAL_TUTOR_SURVEY_ACCESS';
    public const FILL_EDUCATIONAL_TUTOR_SURVEY = 'WLT_TEACHER_EDUCATIONAL_TUTOR_SURVEY_FILL';

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly ProjectRepository $projectRepository,
        private readonly UserExtensionService $userExtensionService
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
            self::ACCESS_EDUCATIONAL_TUTOR_SURVEY,
            self::FILL_EDUCATIONAL_TUTOR_SURVEY
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
        if ($subject->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }
        return match ($attribute) {
            self::ACCESS_EDUCATIONAL_TUTOR_SURVEY => $this->checkAccessPermission($subject, $user),
            // El permiso de rellenar la encuesta es el mismo que el de acceso salvo por el hecho
            // de que se deshabilita en los cursos académicos no activos
            self::FILL_EDUCATIONAL_TUTOR_SURVEY => $this->checkAccessPermission($subject, $user)
                && $subject->getAcademicYear() === $organization->getCurrentAcademicYear(),
            // denegamos en cualquier otro caso
            default => false,
        };
    }

    private function checkAccessPermission(Teacher $subject, Person $user): bool
    {
        $projects = $this->projectRepository->findByEducationalTutor($subject);

        // El propio docente puede ver sus encuestas siempre
        if ($subject->getPerson() === $user) {
            return true;
        }
        // El coordinador/a de sus proyectos
        /** @var Project $project */
        foreach ($projects as $project) {
            if ($project->getManager() === $user) {
                return true;
            }
            $groups = $project->getGroups();
            // El jefe de departamento de los grupos donde enseña
            /** @var Group $group */
            foreach ($groups as $group) {
                if ($group->getGrade()->getTraining()->getDepartment() &&
                    $group->getGrade()->getTraining()->getDepartment()->getHead() &&
                    $group->getGrade()->getTraining()
                        ->getDepartment()->getHead()->getPerson() === $user) {
                    return true;
                }
            }
        }

        return false;
    }
}
