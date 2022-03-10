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

namespace App\Security\Edu;

use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Repository\Edu\TeacherRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AcademicYearVoter extends CachedVoter
{
    public const MANAGE = 'ACADEMIC_YEAR_MANAGE';
    public const ACCESS = 'ACADEMIC_YEAR_ACCESS';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var UserExtensionService $userExtensionService */
    private $userExtensionService;

    /** @var TeacherRepository */
    private $teacherRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPool,
        AccessDecisionManagerInterface $decisionManager,
        UserExtensionService $userExtensionService,
        TeacherRepository $teacherRepository
    ) {
        parent::__construct($cacheItemPool);
        $this->decisionManager = $decisionManager;
        $this->userExtensionService = $userExtensionService;
        $this->teacherRepository = $teacherRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof AcademicYear) {
            return false;
        }
        return in_array($attribute, [self::MANAGE, self::ACCESS], true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof AcademicYear) {
            return false;
        }

        // si el curso académico no pertenece a la organización actual, denegar
        $organization = $this->userExtensionService->getCurrentOrganization();
        if ($subject->getOrganization() !== $organization) {
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

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        // Si es permiso de acceso, comprobar que es un profesor de ese curso académico
        if ($attribute === self::ACCESS) {
            return null !== $this->teacherRepository->findOneByPersonAndAcademicYear($user, $subject);
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
