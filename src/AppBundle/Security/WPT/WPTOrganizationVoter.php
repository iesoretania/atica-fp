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

namespace AppBundle\Security\WPT;

use AppBundle\Entity\Organization;
use AppBundle\Entity\User;
use AppBundle\Repository\WPT\AgreementRepository;
use AppBundle\Repository\WPT\WPTGroupRepository;
use AppBundle\Security\CachedVoter;
use AppBundle\Security\Edu\EduOrganizationVoter;
use AppBundle\Security\OrganizationVoter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class WPTOrganizationVoter extends CachedVoter
{
    const WPT_ACCESS = 'ORGANIZATION_ACCESS_WORKPLACE_TRAINING';
    const WPT_MANAGE = 'ORGANIZATION_MANAGE_WORKPLACE_TRAINING';

    const WPT_GROUP_TUTOR = 'ORGANIZATION_WPT_GROUP_TUTOR';
    const WPT_WORK_TUTOR = 'ORGANIZATION_WPT_WORK_TUTOR';
    const WPT_STUDENT = 'ORGANIZATION_WPT_STUDENT';
    const WPT_EDUCATIONAL_TUTOR = 'ORGANIZATION_WPT_EDUCATIONAL_TUTOR';
    const WPT_DEPARTMENT_HEAD = 'ORGANIZATION_WPT_DEPARTMENT_HEAD';

    private $decisionManager;
    private $wptGroupRepository;
    private $agreementRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        WPTGroupRepository $wptGroupRepository,
        AgreementRepository $agreementRepository,
        AccessDecisionManagerInterface $decisionManager
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->wptGroupRepository = $wptGroupRepository;
        $this->agreementRepository = $agreementRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Organization) {
            return false;
        }

        if (!in_array($attribute, [
            self::WPT_ACCESS,
            self::WPT_MANAGE,
            self::WPT_WORK_TUTOR,
            self::WPT_GROUP_TUTOR,
            self::WPT_STUDENT,
            self::WPT_EDUCATIONAL_TUTOR,
            self::WPT_DEPARTMENT_HEAD
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
        if (!$subject instanceof Organization) {
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

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)
        ) {
            return true;
        }
        switch ($attribute) {
            case self::WPT_MANAGE:
                // Si es jefe de algún departamento, permitir acceder
                // Jefe de departamento
                return $this->decisionManager->decide($token, [EduOrganizationVoter::EDU_DEPARTMENT_HEAD], $subject);

            case self::WPT_ACCESS:
                // pueden acceder:
                // 1) los tutores de grupo donde haya FCT
                // 2) los jefes de departamento
                // 3) los tutores laborales y docentes de los acuerdos de colaboración y
                // 4) los estudiantes que tengan acuerdos

                // 1) Tutores de grupo de FP dual
                if ($this->decisionManager->decide($token, [self::WPT_GROUP_TUTOR], $subject)) {
                    return true;
                }

                // 2) Jefe de departamento
                if ($this->decisionManager->decide($token, [self::WPT_DEPARTMENT_HEAD], $subject)) {
                    return true;
                }

                // 3) Tutores laborales y docentes
                if ($this->decisionManager->decide($token, [self::WPT_WORK_TUTOR], $subject) ||
                    $this->decisionManager->decide($token, [self::WPT_EDUCATIONAL_TUTOR], $subject)) {
                    return true;
                }

                // 4) Alumnado con acuerdos, sólo si es acceso
                return $attribute === self::WPT_ACCESS &&
                    $this->decisionManager->decide($token, [self::WPT_STUDENT], $subject);

            case self::WPT_GROUP_TUTOR:
                if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)) {
                    return true;
                }
                return
                    $this->wptGroupRepository->countAcademicYearAndWPTGroupTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WPT_STUDENT:
                if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndStudentPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WPT_WORK_TUTOR:
                if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndWorkTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WPT_EDUCATIONAL_TUTOR:
                if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndEducationalTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WPT_DEPARTMENT_HEAD:
                return
                    $this->wptGroupRepository->countAcademicYearAndWPTDepartmentHeadPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
