<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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
use App\Entity\WLT\Meeting;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class MeetingVoter extends CachedVoter
{
    public const MANAGE = 'WLT_MEETING_MANAGE';
    public const ACCESS = 'WLT_MEETING_ACCESS';

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

        if (!$subject instanceof Meeting) {
            return false;
        }
        return in_array($attribute, [
            self::MANAGE,
            self::ACCESS
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Meeting) {
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
        if ($subject->getCreatedBy()
            && $subject->getCreatedBy()->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        switch ($attribute) {
            case self::MANAGE:
                // El propio docente puede gestionar sus reuniones si es del curso académico actual
                return $subject->getCreatedBy()->getPerson() === $user
                    && $subject->getCreatedBy()->getAcademicYear() === $organization->getCurrentAcademicYear();
            case self::ACCESS:
                // Puede acceder a los datos de la visita el propio docente
                if ($subject->getCreatedBy()
                    && $subject->getCreatedBy()->getPerson() === $user) {
                    return true;
                }
                // Puede acceder a los datos de la visita cualquier docente asociado a ella
                foreach ($subject->getTeachers() as $teacher) {
                    if ($teacher->getPerson() === $user) {
                        return true;
                    }
                }
                // Puede acceder el coordinador de cualquier proyecto asociado a las visitas, el jefe/a de departamento
                // de los grupos de los proyectos
                $project = $subject->getProject();
                if ($project->getManager() === $user) {
                    return true;
                }
                foreach ($project->getGroups() as $group) {
                    if ($group->getGrade()->getTraining()->getDepartment() &&
                        $group->getGrade()->getTraining()->getDepartment()->getHead() &&
                        $group->getGrade()->getTraining()->getDepartment()->getHead()->getPerson()
                            === $user) {
                        return true;
                    }
                }

                // Puede acceder el tutor de los estudiantes visitados
                foreach ($subject->getStudentEnrollments() as $studentEnrollment) {
                    foreach ($studentEnrollment->getGroup()->getTutors() as $tutor) {
                        if ($tutor->getPerson() === $user) {
                            return true;
                        }
                    }
                }
                return false;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
