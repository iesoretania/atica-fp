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

namespace App\Security\WptModule;

use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\WptModule\Agreement;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AgreementVoter extends CachedVoter
{
    public const MANAGE = 'WPT_AGREEMENT_MANAGE';
    public const ACCESS = 'WPT_AGREEMENT_ACCESS';

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

        if (!$subject instanceof Agreement) {
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
        if (!$subject instanceof Agreement) {
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
        if (!$organization instanceof Organization ||
            !$organization->getCurrentAcademicYear()->hasModule('wpt')) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        // si es de otra organización, denegar
        if ($organization !== $this->userExtensionService->getCurrentOrganization()) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $person = $user;

        $agreementIsLocked = $subject->getShift()->getGrade()
            && $subject->getShift()->getGrade()->getTraining()->getAcademicYear()
            === $organization->getCurrentAcademicYear();

        // es jefe de departamento de la enseñanza de la convocatoria
        $isDepartmentHead = $subject->getShift()->getGrade()
            && $subject->getShift()->getGrade()->getTraining()->getDepartment()
            && $subject->getShift()->getGrade()->getTraining()->getDepartment()->getHead()
            && $subject->getShift()->getGrade()->getTraining()->getDepartment()->getHead()->getPerson() === $person;

        if ($subject->isLocked() || $subject->getShift()->isLocked()) {
            $agreementIsLocked = true;
        }

        switch ($attribute) {
            case self::MANAGE:
                if ($isDepartmentHead) {
                    return $agreementIsLocked;
                }
                return false;

            // Si es permiso de acceso, comprobar si es el estudiante, docente, el tutor de grupo o
            // el responsable laboral
            case self::ACCESS:
                return $isDepartmentHead;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
