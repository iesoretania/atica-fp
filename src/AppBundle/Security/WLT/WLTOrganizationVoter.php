<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

use AppBundle\Entity\Organization;
use AppBundle\Entity\User;
use AppBundle\Repository\WLT\AgreementRepository;
use AppBundle\Repository\WLT\ProjectRepository;
use AppBundle\Repository\WLT\WLTGroupRepository;
use AppBundle\Security\CachedVoter;
use AppBundle\Security\Edu\EduOrganizationVoter;
use AppBundle\Security\OrganizationVoter;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class WLTOrganizationVoter extends CachedVoter
{
    const WLT_ACCESS = 'ORGANIZATION_ACCESS_WORKLINKED_TRAINING';
    const WLT_MANAGE = 'ORGANIZATION_MANAGE_WORKLINKED_TRAINING';
    const WLT_GRADE = 'ORGANIZATION_GRADE_WORKLINKED_TRAINING';
    const WLT_VIEW_GRADE = 'ORGANIZATION_VIEW_GRADE_WORKLINKED_TRAINING';
    const WLT_VIEW_EVALUATION = 'ORGANIZATION_VIEW_EVALUATION_WORKLINKED_TRAINING';
    const WLT_ACCESS_VISIT = 'ORGANIZATION_ACCESS_WORKLINKED_TRAINING_VISIT';
    const WLT_MANAGE_VISIT = 'ORGANIZATION_MANAGE_WORKLINKED_TRAINING_VISIT';

    const WLT_GROUP_TUTOR = 'ORGANIZATION_WLT_GROUP_TUTOR';
    const WLT_WORK_TUTOR = 'ORGANIZATION_WLT_WORK_TUTOR';
    const WLT_STUDENT = 'ORGANIZATION_WLT_STUDENT';
    const WLT_TEACHER = 'ORGANIZATION_WLT_TEACHER';
    const WLT_MANAGER = 'ORGANIZATION_WLT_MANAGER';
    const WLT_EDUCATIONAL_TUTOR = 'ORGANIZATION_WLT_EDUCATIONAL_TUTOR';
    const WLT_DEPARTMENT_HEAD = 'ORGANIZATION_WLT_DEPARTMENT_HEAD';

    private $decisionManager;
    private $agreementRepository;
    private $projectRepository;
    private $WLTGroupRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        AgreementRepository $agreementRepository,
        ProjectRepository $projectRepository,
        WLTGroupRepository $WLTGroupRepository
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->agreementRepository = $agreementRepository;
        $this->projectRepository = $projectRepository;
        $this->WLTGroupRepository = $WLTGroupRepository;
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
            self::WLT_ACCESS,
            self::WLT_MANAGE,
            self::WLT_GRADE,
            self::WLT_VIEW_GRADE,
            self::WLT_VIEW_EVALUATION,
            self::WLT_ACCESS_VISIT,
            self::WLT_MANAGE_VISIT,
            self::WLT_WORK_TUTOR,
            self::WLT_GROUP_TUTOR,
            self::WLT_STUDENT,
            self::WLT_TEACHER,
            self::WLT_MANAGER,
            self::WLT_EDUCATIONAL_TUTOR,
            self::WLT_DEPARTMENT_HEAD
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
            case self::WLT_MANAGE_VISIT:
                // coordinadores de proyectos de FP dual, ok
                if ($this->decisionManager->decide($token, [self::WLT_MANAGER], $subject)) {
                    return true;
                }

                // jefes de departamento: solo ver
                if ($attribute === self::WLT_ACCESS_VISIT &&
                    $this->decisionManager->decide($token, [self::WLT_DEPARTMENT_HEAD], $subject)) {
                    return true;
                }

                // tutores de seguimiento
                return $this->decisionManager->decide($token, [self::WLT_EDUCATIONAL_TUTOR], $subject);

            case self::WLT_MANAGER:
                return $this->projectRepository->countByOrganizationAndManagerPerson($subject, $user->getPerson()) > 0;

            case self::WLT_GROUP_TUTOR:
                return
                    $this->WLTGroupRepository->countAcademicYearAndWLTGroupTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WLT_STUDENT:
                return
                    $this->agreementRepository->countAcademicYearAndStudentPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WLT_WORK_TUTOR:
                return
                    $this->agreementRepository->countAcademicYearAndWorkTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WLT_TEACHER:
                return
                    $this->WLTGroupRepository->countAcademicYearAndWLTTeacherPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WLT_EDUCATIONAL_TUTOR:
                return
                    $this->agreementRepository->countAcademicYearAndEducationalTutorPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WLT_DEPARTMENT_HEAD:
                return
                    $this->WLTGroupRepository->countAcademicYearAndWLTDepartmentHeadPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
