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

use App\Entity\Organization;
use App\Entity\Person;
use App\Repository\Edu\TeacherRepository;
use App\Repository\Edu\TrainingRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class EduOrganizationVoter extends CachedVoter
{
    public const EDU_DEPARTMENT_HEAD = 'ORGANIZATION_DEPARTMENT_HEAD';
    public const EDU_TEACHER = 'ORGANIZATION_TEACHER';
    public const EDU_FINANCIAL_MANAGER = 'ORGANIZATION_FINANCIAL_MANAGER';

    private $decisionManager;
    private $trainingRepository;
    private $teacherRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        TrainingRepository $trainingRepository,
        TeacherRepository $teacherRepository
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->trainingRepository = $trainingRepository;
        $this->teacherRepository = $teacherRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Organization) {
            return false;
        }
        return in_array($attribute, [
            self::EDU_DEPARTMENT_HEAD,
            self::EDU_TEACHER,
            self::EDU_FINANCIAL_MANAGER
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof Organization) {
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
        if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)
        ) {
            return true;
        }

        switch ($attribute) {
            case self::EDU_DEPARTMENT_HEAD:
                return
                    $this->trainingRepository->countAcademicYearAndDepartmentHead(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::EDU_TEACHER:
                return
                    $this->teacherRepository->findOneByAcademicYearAndPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) !== null;

            case self::EDU_FINANCIAL_MANAGER:
                $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson(
                    $subject->getCurrentAcademicYear(),
                    $user);

                return
                    $teacher && $subject->getCurrentAcademicYear()->getFinancialManager()
                        === $teacher;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
