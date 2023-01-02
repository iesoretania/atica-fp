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

namespace App\Security\Edu;

use App\Entity\Edu\Group;
use App\Entity\Person;
use App\Repository\Edu\TeachingRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class GroupVoter extends CachedVoter
{
    public const MANAGE = 'EDU_GROUP_MANAGE';
    public const ACCESS = 'EDU_GROUP_ACCESS';
    public const TEACH = 'EDU_GROUP_TEACH';

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
        return in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
            self::TEACH
        ], true);
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

        /** @var Person $user */
        $user = $token->getUser();

        if (!$user instanceof Person) {
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
            $training->getDepartment()->getHead()->getPerson() === $user
        ) {
            return true;
        }

        $isGroupTutor = false;

        // tutores del grupo
        $tutors = $subject->getTutors();
        foreach ($tutors as $tutor) {
            if ($tutor->getPerson() === $user) {
                $isGroupTutor = true;
                break;
            }
        }

        // profesor del grupo del acuerdo
        $isTeacher = $this->teachingRepository->countByGroupAndPerson(
            $subject,
            $user
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
