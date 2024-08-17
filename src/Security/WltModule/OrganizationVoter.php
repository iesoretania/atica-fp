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

namespace App\Security\WltModule;

use App\Entity\Organization;
use App\Entity\Person;
use App\Repository\WltModule\AgreementRepository;
use App\Repository\WltModule\ProjectRepository;
use App\Repository\WltModule\GroupRepository;
use App\Security\CachedVoter;
use App\Security\Edu\OrganizationVoter as EduOrganizationVoter;
use App\Security\OrganizationVoter as BaseOrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class OrganizationVoter extends CachedVoter
{
    public const WLT_ACCESS = 'WLT_ORGANIZATION_ACCESS';
    public const WLT_MANAGE = 'WLT_ORGANIZATION_MANAGE';
    public const WLT_GRADE = 'WLT_ORGANIZATION_GRADE';
    public const WLT_VIEW_GRADE = 'WLT_ORGANIZATION_VIEW_GRADE';
    public const WLT_VIEW_EVALUATION = 'WLT_ORGANIZATION_VIEW_EVALUATION';
    public const WLT_ACCESS_VISIT = 'WLT_ORGANIZATION_ACCESS_VISIT';
    public const WLT_CREATE_VISIT = 'WLT_ORGANIZATION_CREATE_VISIT';
    public const WLT_ACCESS_MEETING = 'WLT_ORGANIZATION_ACCESS_WORKLINKED_MEETING_VISIT';
    public const WLT_CREATE_MEETING = 'WLT_ORGANIZATION_CREATE_WORKLINKED_MEETING_VISIT';

    public const WLT_GROUP_TUTOR = 'WLT_ORGANIZATION_GROUP_TUTOR';
    public const WLT_WORK_TUTOR = 'WLT_ORGANIZATION_WORK_TUTOR';
    public const WLT_STUDENT = 'WLT_ORGANIZATION_STUDENT';
    public const WLT_TEACHER = 'WLT_ORGANIZATION_TEACHER';
    public const WLT_MANAGER = 'WLT_ORGANIZATION_MANAGER';
    public const WLT_EDUCATIONAL_TUTOR = 'WLT_ORGANIZATION_EDUCATIONAL_TUTOR';
    public const WLT_DEPARTMENT_HEAD = 'WLT_ORGANIZATION_DEPARTMENT_HEAD';

    public const WLT_ACCESS_EXPENSE = 'WLT_ORGANIZATION_ACCESS_EXPENSE';
    public const WLT_CREATE_EXPENSE = 'WLT_ORGANIZATION_CREATE_EXPENSE';

    public function __construct(
        CacheItemPoolInterface                          $cacheItemPoolItemPool,
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly AgreementRepository            $agreementRepository,
        private readonly ProjectRepository              $projectRepository,
        private readonly GroupRepository                $WLTGroupRepository,
        private readonly UserExtensionService           $userExtensionService
    ) {
        parent::__construct($cacheItemPoolItemPool);
    }

    /**
     * {@inheritdoc}
     */
    final public function supports($attribute, $subject): bool
    {

        if (!$subject instanceof Organization) {
            return false;
        }
        return in_array($attribute, [
            self::WLT_ACCESS,
            self::WLT_MANAGE,
            self::WLT_GRADE,
            self::WLT_VIEW_GRADE,
            self::WLT_VIEW_EVALUATION,
            self::WLT_ACCESS_VISIT,
            self::WLT_CREATE_VISIT,
            self::WLT_ACCESS_MEETING,
            self::WLT_CREATE_MEETING,
            self::WLT_WORK_TUTOR,
            self::WLT_GROUP_TUTOR,
            self::WLT_STUDENT,
            self::WLT_TEACHER,
            self::WLT_MANAGER,
            self::WLT_EDUCATIONAL_TUTOR,
            self::WLT_DEPARTMENT_HEAD,
            self::WLT_ACCESS_EXPENSE,
            self::WLT_CREATE_EXPENSE
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Organization) {
            return false;
        }

        /** @var Person $user */
        $user = $token->getUser();

        if (!$user instanceof Person) {
            // si el usuario no ha entrado, denegar
            return false;
        }

        // si el módulo está deshabilitado, denegar
        if (!$this->userExtensionService->getCurrentOrganization() instanceof Organization ||
            !$this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear()->hasModule('wlt')) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [BaseOrganizationVoter::LOCAL_MANAGE], $subject)
        ) {
            return true;
        }

        switch ($attribute) {
            case self::WLT_MANAGE:
                // Si es jefe de algún departamento o coordinador de FP dual, permitir acceder
                // 1) Jefe de departamento
                if ($this->decisionManager->decide($token, [EduOrganizationVoter::EDU_DEPARTMENT_HEAD], $subject)) {
                    return true;
                }

                // 2) Coordinador de FP dual
                return $this->decisionManager->decide($token, [self::WLT_MANAGER], $subject);

            case self::WLT_VIEW_GRADE:
            case self::WLT_VIEW_EVALUATION:
            case self::WLT_GRADE:
            case self::WLT_ACCESS:
                // pueden acceder:
                // 1) los que gestionan la FP dual,
                // 2) los profesores que imparten en dual,
                // 3) los tutores de grupo duales,
                // 4) los jefes de departamento,
                // 5) los tutores laborales y docentes de los acuerdos de colaboración y
                // 6) los estudiantes que tengan acuerdos

                // 1) Coordinador de FP dual
                if ($this->decisionManager->decide($token, [self::WLT_MANAGER], $subject)) {
                    return true;
                }

                // 2) Profesores que imparten en FP Dual
                if ($this->decisionManager->decide($token, [self::WLT_TEACHER], $subject)) {
                    return true;
                }

                // 3) Tutores de grupo de FP dual
                if ($this->decisionManager->decide($token, [self::WLT_GROUP_TUTOR], $subject)) {
                    return true;
                }

                // 4) Jefe de departamento
                if ($this->decisionManager->decide($token, [self::WLT_DEPARTMENT_HEAD], $subject)) {
                    return true;
                }

                // el tutor laboral no puede ver la evaluación numérica
                if ($attribute === self::WLT_VIEW_GRADE) {
                    return false;
                }

                // 5) Tutores laborales
                if ($this->decisionManager->decide($token, [self::WLT_WORK_TUTOR], $subject) ||
                    $this->decisionManager->decide($token, [self::WLT_EDUCATIONAL_TUTOR], $subject)) {
                    return true;
                }

                // 6) Docentes de dual, salvo que sea realizar evaluación
                if ($attribute !== self::WLT_GRADE &&
                    $this->decisionManager->decide($token, [self::WLT_TEACHER], $subject)) {
                    return true;
                }

                // 7) Alumnado con acuerdos, sólo si es acceso
                return $attribute === self::WLT_ACCESS &&
                    $this->decisionManager->decide($token, [self::WLT_STUDENT], $subject);

            case self::WLT_ACCESS_VISIT:
            case self::WLT_CREATE_VISIT:
            case self::WLT_ACCESS_MEETING:
            case self::WLT_CREATE_MEETING:
                // coordinadores de proyectos de FP dual, ok
                if ($this->decisionManager->decide($token, [self::WLT_MANAGER], $subject)) {
                    return true;
                }

                // jefes de departamento: solo ver
                // tutores de seguimiento
                // profesorado de dual
                return $this->decisionManager->decide($token, [self::WLT_DEPARTMENT_HEAD], $subject) ||
                    $this->decisionManager->decide($token, [self::WLT_EDUCATIONAL_TUTOR], $subject) ||
                    $this->decisionManager->decide($token, [self::WLT_TEACHER], $subject);

            case self::WLT_MANAGER:
                return $this->projectRepository->countByOrganizationAndManagerPerson($subject, $user) > 0;

            case self::WLT_GROUP_TUTOR:
                if ($this->decisionManager->decide($token, [self::WLT_MANAGER], $subject)) {
                    return true;
                }
                return
                    $this->WLTGroupRepository->countAcademicYearAndWLTGroupTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WLT_STUDENT:
                if ($this->decisionManager->decide($token, [self::WLT_MANAGER], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndStudentPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WLT_WORK_TUTOR:
                if ($this->decisionManager->decide($token, [self::WLT_MANAGER], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndWorkTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WLT_TEACHER:
                if ($this->decisionManager->decide($token, [self::WLT_MANAGER], $subject)) {
                    return true;
                }
                return
                    $this->WLTGroupRepository->countAcademicYearAndWLTTeacherPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WLT_EDUCATIONAL_TUTOR:
                if ($this->decisionManager->decide($token, [self::WLT_MANAGER], $subject)) {
                    return true;
                }
                return
                    $this->agreementRepository->countAcademicYearAndEducationalTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WLT_DEPARTMENT_HEAD:
                return
                    $this->WLTGroupRepository->countAcademicYearAndWLTDepartmentHeadPerson(
                        $subject->getCurrentAcademicYear(),
                        $user
                    ) > 0;

            case self::WLT_ACCESS_EXPENSE:
                // 1) Todos los que pueden acceder a las visitas
                // 2) Gestor económico del centro
                return $this->decisionManager->decide($token, [self::WLT_ACCESS_VISIT], $subject) ||
                    $this->decisionManager->decide($token, [EduOrganizationVoter::EDU_FINANCIAL_MANAGER], $subject);

            case self::WLT_CREATE_EXPENSE:
                // 1) Todos los que pueden crear visitas
                // 2) Gestor económico del centro
                return $this->decisionManager->decide($token, [self::WLT_CREATE_VISIT], $subject) ||
                    $this->decisionManager->decide($token, [EduOrganizationVoter::EDU_FINANCIAL_MANAGER], $subject);

        }

        // denegamos en cualquier otro caso
        return false;
    }
}
