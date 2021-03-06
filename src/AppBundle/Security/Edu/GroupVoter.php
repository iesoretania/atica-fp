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

namespace AppBundle\Security\Edu;

use AppBundle\Entity\Edu\Group;
use AppBundle\Entity\User;
use AppBundle\Repository\Edu\TeachingRepository;
use AppBundle\Security\CachedVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class GroupVoter extends CachedVoter
{
    const MANAGE = 'EDU_GROUP_MANAGE';
    const ACCESS = 'EDU_GROUP_ACCESS';
    const TEACH = 'EDU_GROUP_TEACH';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var UserExtensionService */
    private $userExtensionService;

    /** @var TeachingRepository */
    private $teachingRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        TeachingRepository $teachingRepository,
        UserExtensionService $userExtensionService
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->teachingRepository = $teachingRepository;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Group) {
            return false;
        }

        if (!in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
            self::TEACH
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
        if (!$subject instanceof Group) {
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

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        // Si es jefe de su departamento o coordinador de FP dual, permitir acceder
        // 1) Jefe del departamento del ciclo formativo del grupo
        $training = $subject->getGrade()->getTraining();
        if (null !== $training->getDepartment() && $training->getDepartment()->getHead() &&
            $training->getDepartment()->getHead()->getPerson() === $user->getPerson()
        ) {
            return true;
        }

        $isGroupTutor = false;

        // tutores del grupo
        $tutors = $subject->getTutors();
        foreach ($tutors as $tutor) {
            if ($tutor->getPerson()->getUser() === $user) {
                $isGroupTutor = true;
                break;
            }
        }

        // profesor del grupo del acuerdo
        $isTeacher = $this->teachingRepository->countByGroupAndPerson(
            $subject,
            $user->getPerson()
        ) > 0;

        switch ($attribute) {
            // Si es permiso de gestión, el tutor de grupo
            case self::MANAGE:
                return $isGroupTutor;

            // Si es permiso de acceso, el tutor de grupo o un docente
            case self::ACCESS:
                return $isTeacher || $isGroupTutor;

            // Si es permiso para enseñar, un docente
            case self::TEACH:
                return $isTeacher;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
