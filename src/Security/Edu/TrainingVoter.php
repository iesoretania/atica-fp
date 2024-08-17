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

namespace App\Security\Edu;

use App\Entity\Edu\Training;
use App\Entity\Person;
use App\Repository\Edu\TeacherRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Security\WltModule\OrganizationVoter as WltOrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class TrainingVoter extends CachedVoter
{
    public const MANAGE = 'TRAINING_MANAGE';
    public const ACCESS = 'TRAINING_ACCESS';

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        private readonly UserExtensionService $userExtensionService,
        private readonly TeacherRepository $teacherRepository,
        private readonly Security $security
    ) {
        parent::__construct($cacheItemPoolItemPool);
    }

    /**
     * {@inheritdoc}
     */
    final public function supports($attribute, $subject): bool
    {

        if (!$subject instanceof Training) {
            return false;
        }
        return in_array($attribute, [self::MANAGE, self::ACCESS], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Training) {
            return false;
        }

        // si la enseñanza no pertenece a la organización actual, denegar
        $organization = $this->userExtensionService->getCurrentOrganization();
        if ($subject->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->userExtensionService->isUserGlobalAdministrator()) {
            return true;
        }

        /** @var Person $user */
        $user = $token->getUser();

        if (!$user instanceof Person) {
            // si el usuario no ha entrado, denegar
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->security->isGranted(OrganizationVoter::MANAGE, $organization)) {
            return true;
        }

        // Si es el coordinador de FP dual, permitir si el ciclo es dual
        if ($this->security->isGranted(WltOrganizationVoter::WLT_MANAGER, $organization)) {
            return true;
        }

        // Si es el jefe de departamento de la enseñanza, permitir siempre
        if ($subject->getDepartment() && $subject->getDepartment()->getHead()->getPerson() === $user) {
            return true;
        }

        // Si es permiso de acceso, comprobar que es un profesor de ese curso académico
        if ($attribute === self::ACCESS) {
            return null !== $this->teacherRepository->
                findOneByPersonAndAcademicYear($user, $subject->getAcademicYear());
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
