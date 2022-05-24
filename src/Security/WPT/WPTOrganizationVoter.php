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

namespace App\Security\WPT;

use App\Entity\Organization;
use App\Entity\Person;
use App\Repository\WPT\AgreementRepository;
use App\Repository\WPT\WPTGroupRepository;
use App\Security\CachedVoter;
use App\Security\Edu\EduOrganizationVoter;
use App\Security\OrganizationVoter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class WPTOrganizationVoter extends CachedVoter
{
    public const WPT_ACCESS_SECTION = 'ORGANIZATION_ACCESS_WORKPLACE_TRAINING';
    public const WPT_ACCESS = 'ORGANIZATION_ACCESS_WORKPLACE_TRAINING_TRACKING';
    public const WPT_MANAGER = 'ORGANIZATION_MANAGE_WORKPLACE_TRAINING_TRACKING';
    public const WPT_FILL_REPORT = 'ORGANIZATION_FILL_REPORT_WORKPLACE_TRAINING';

    public const WPT_GROUP_TUTOR = 'ORGANIZATION_WPT_GROUP_TUTOR';
    public const WPT_WORK_TUTOR = 'ORGANIZATION_WPT_WORK_TUTOR';
    public const WPT_STUDENT = 'ORGANIZATION_WPT_STUDENT';
    public const WPT_EDUCATIONAL_TUTOR = 'ORGANIZATION_WPT_EDUCATIONAL_TUTOR';
    public const WPT_DEPARTMENT_HEAD = 'ORGANIZATION_WPT_DEPARTMENT_HEAD';

    public const WPT_ACCESS_VISIT = 'ORGANIZATION_WPT_ACCESS_VISIT';
    public const WPT_CREATE_VISIT = 'ORGANIZATION_WPT_CREATE_VISIT';

    public const WPT_ACCESS_EXPENSE = 'ORGANIZATION_WPT_ACCESS_EXPENSE';
    public const WPT_CREATE_EXPENSE = 'ORGANIZATION_WPT_CREATE_EXPENSE';

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
        return in_array($attribute, [
            self::WPT_ACCESS_SECTION,
            self::WPT_ACCESS,
            self::WPT_MANAGER,
            self::WPT_FILL_REPORT,
            self::WPT_WORK_TUTOR,
            self::WPT_GROUP_TUTOR,
            self::WPT_STUDENT,
            self::WPT_EDUCATIONAL_TUTOR,
            self::WPT_DEPARTMENT_HEAD,
            self::WPT_CREATE_VISIT,
            self::WPT_ACCESS_VISIT,
            self::WPT_ACCESS_EXPENSE,
            self::WPT_CREATE_EXPENSE
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
            case self::WPT_MANAGER:
                // Si es jefe de algún departamento, permitir acceder
                // Jefe de departamento
                return $this->decisionManager->decide($token, [EduOrganizationVoter::EDU_DEPARTMENT_HEAD], $subject);

            case self::WPT_ACCESS:
                // pueden acceder:
                // 1) los tutores de grupo donde haya FCT
                // 2) los jefes de departamento
                // 3) los tutores laborales y docentes de los acuerdos de colaboración y
                // 4) El gestor económico (secretario) del centro
                // 5) los estudiantes que tengan acuerdos

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

            case self::WPT_ACCESS_SECTION:
                // 1) Todos los que puedan ver el seguimiento
                // 2) Todos los que pueden ver las visitas
                // 3) Todos los que pueden ver los desplazamientos
                return $this->decisionManager->decide($token, [self::WPT_ACCESS], $subject) ||
                    $this->decisionManager->decide($token, [self::WPT_ACCESS_VISIT], $subject) ||
                    $this->decisionManager->decide($token, [EduOrganizationVoter::EDU_FINANCIAL_MANAGER], $subject);

            case self::WPT_ACCESS_EXPENSE:
                // 1) Todos los que pueden acceder a las visitas
                // 2) Gestor económico del centro
                return $this->decisionManager->decide($token, [self::WPT_ACCESS_VISIT], $subject) ||
                    $this->decisionManager->decide($token, [EduOrganizationVoter::EDU_FINANCIAL_MANAGER], $subject);

            case self::WPT_CREATE_EXPENSE:
                // 1) Todos los que pueden crear visitas
                // 2) Gestor económico del centro
                return $this->decisionManager->decide($token, [self::WPT_CREATE_VISIT], $subject) ||
                    $this->decisionManager->decide($token, [EduOrganizationVoter::EDU_FINANCIAL_MANAGER], $subject);

            case self::WPT_FILL_REPORT:
                // pueden acceder al informe:
                // 1) los tutores de grupo donde haya FCT
                // 2) los jefes de departamento
                // 3) los tutores laborales y docentes de los acuerdos de colaboración

                // 1) Tutores de grupo de FP dual
                if ($this->decisionManager->decide($token, [self::WPT_GROUP_TUTOR], $subject)) {
                    return true;
                }

                // 2) Jefe de departamento
                if ($this->decisionManager->decide($token, [self::WPT_DEPARTMENT_HEAD], $subject)) {
                    return true;
                }
                // 3) Tutores laborales y docentes
                return $this->decisionManager->decide($token, [self::WPT_WORK_TUTOR], $subject) ||
                    $this->decisionManager->decide($token, [self::WPT_EDUCATIONAL_TUTOR], $subject);

            case self::WPT_CREATE_VISIT:
            case self::WPT_ACCESS_VISIT:
                // pueden crear visitas:
                // 1) los jefes de departamento
                // 2) los tutores docentes de los acuerdos de colaboración
                return ($this->decisionManager->decide($token, [self::WPT_DEPARTMENT_HEAD], $subject) ||
                    $this->decisionManager->decide($token, [self::WPT_EDUCATIONAL_TUTOR], $subject));

            case self::WPT_GROUP_TUTOR:
                if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)) {
                    return true;
                }
                return
                    $this->wptGroupRepository->countAcademicYearAndWPTGroupTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WPT_STUDENT:
                if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndStudentPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WPT_WORK_TUTOR:
                if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndWorkTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WPT_EDUCATIONAL_TUTOR:
                if ($this->decisionManager->decide($token, [OrganizationVoter::LOCAL_MANAGE], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndEducationalTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WPT_DEPARTMENT_HEAD:
                return
                    $this->wptGroupRepository->countAcademicYearAndWPTDepartmentHeadPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
