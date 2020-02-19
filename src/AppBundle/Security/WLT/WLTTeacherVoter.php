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

namespace AppBundle\Security\WLT;

use AppBundle\Entity\Edu\Group;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\User;
use AppBundle\Entity\WLT\Project;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Security\CachedVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class WLTTeacherVoter extends CachedVoter
{
    const ACCESS_EDUCATIONAL_TUTOR_SURVEY = 'WLT_TEACHER_EDUCATIONAL_TUTOR_SURVEY_ACCESS';
    const FILL_EDUCATIONAL_TUTOR_SURVEY = 'WLT_TEACHER_EDUCATIONAL_TUTOR_SURVEY_FILL';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var UserExtensionService */
    private $userExtensionService;
    /**
     * @var ProjectRepository
     */
    private $projectRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        ProjectRepository $projectRepository,
        UserExtensionService $userExtensionService
    )
    {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->userExtensionService = $userExtensionService;
        $this->projectRepository = $projectRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Teacher) {
            return false;
        }
        if (!in_array($attribute, [
            self::ACCESS_EDUCATIONAL_TUTOR_SURVEY,
            self::FILL_EDUCATIONAL_TUTOR_SURVEY
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
        if (!$subject instanceof Teacher) {
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
        if ($subject->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        switch ($attribute) {
            case self::ACCESS_EDUCATIONAL_TUTOR_SURVEY:
                return $this->checkAccessPermission($subject, $user);
            case self::FILL_EDUCATIONAL_TUTOR_SURVEY:
                // El permiso de rellenar la encuesta es el mismo que el de acceso salvo por el hecho
                // de que se deshabilita en los cursos académicos no activos
                return $this->checkAccessPermission($subject, $user)
                    && $subject->getAcademicYear() === $organization->getCurrentAcademicYear();
        }

        // denegamos en cualquier otro caso
        return false;
    }

    private function checkAccessPermission(Teacher $subject, User $user)
    {
        $projects = $this->projectRepository->findByEducationalTutor($subject);

        // El propio docente puede ver sus encuestas siempre
        if ($subject->getPerson() === $user->getPerson()) {
            return true;
        }
        // El coordinador/a de sus proyectos
        /** @var Project $project */
        foreach ($projects as $project) {
            if ($project->getManager() === $user->getPerson()) {
                return true;
            }
            $groups = $project->getGroups();
            // El jefe de departamento de los grupos donde enseña
            /** @var Group $group */
            foreach ($groups as $group) {
                if ($group->getGrade()->getTraining()->getDepartment() &&
                    $group->getGrade()->getTraining()->getDepartment()->getHead() &&
                    $group->getGrade()->getTraining()
                        ->getDepartment()->getHead()->getPerson() === $user->getPerson()) {
                    return true;
                }
            }
        }

        return false;
    }
}
