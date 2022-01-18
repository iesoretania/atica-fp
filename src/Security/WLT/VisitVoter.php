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

use App\Entity\User;
use App\Entity\WLT\Visit;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class VisitVoter extends CachedVoter
{
    const MANAGE = 'WLT_VISIT_MANAGE';
    const ACCESS = 'WLT_VISIT_ACCESS';

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

        if (!$subject instanceof Visit) {
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
        if (!$subject instanceof Visit) {
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

        // Si no es de la organización actual, denegar
        if ($subject->getTeacher()->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        switch ($attribute) {
            case self::MANAGE:
                // El propio docente puede gestionar su visita si es del curso académico actual
                return $subject->getTeacher()->getPerson() === $user->getPerson()
                    && $subject->getTeacher()->getAcademicYear() === $organization->getCurrentAcademicYear();
            case self::ACCESS:
                // Puede acceder a los datos de la visita el propio docente
                if ($subject->getTeacher()->getPerson() === $user->getPerson()) {
                    return true;
                }
                // Puede acceder el coordinador de cualquier proyecto asociado a las visitas, el jefe/a de departamento
                // de los grupos de los proyectos
                foreach ($subject->getProjects() as $project) {
                    if ($project->getManager() === $user->getPerson()) {
                        return true;
                    }
                    foreach ($project->getGroups() as $group) {
                        if ($group->getGrade()->getTraining()->getDepartment() &&
                            $group->getGrade()->getTraining()->getDepartment()->getHead() &&
                            $group->getGrade()->getTraining()->getDepartment()->getHead()->getPerson()
                                === $user->getPerson()) {
                            return true;
                        }
                    }
                }
                // Puede acceder el tutor de los estudiantes visitados
                foreach ($subject->getStudentEnrollments() as $studentEnrollment) {
                    foreach ($studentEnrollment->getGroup()->getTutors() as $tutor) {
                        if ($tutor->getPerson() === $user->getPerson()) {
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
