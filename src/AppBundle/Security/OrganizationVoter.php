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

namespace AppBundle\Security;

use AppBundle\Entity\Membership;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Role;
use AppBundle\Entity\User;
use AppBundle\Repository\Edu\GroupRepository;
use AppBundle\Repository\Edu\TeacherRepository;
use AppBundle\Repository\Edu\TeachingRepository;
use AppBundle\Repository\Edu\TrainingRepository;
use AppBundle\Repository\RoleRepository;
use AppBundle\Repository\WLT\AgreementRepository;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class OrganizationVoter extends CachedVoter
{
    const MANAGE = 'ORGANIZATION_MANAGE';
    const ACCESS = 'ORGANIZATION_ACCESS';
    const ACCESS_TRAININGS = 'ORGANIZATION_ACCESS_TRAININGS';
    const LOCAL_MANAGE = 'ORGANIZATION_LOCAL_MANAGE';
    const ACCESS_WORK_LINKED_TRAINING = 'ORGANIZATION_ACCESS_WORKLINKED_TRAINING';
    const MANAGE_WORK_LINKED_TRAINING = 'ORGANIZATION_MANAGE_WORKLINKED_TRAINING';
    const GRADE_WORK_LINKED_TRAINING = 'ORGANIZATION_GRADE_WORKLINKED_TRAINING';
    const VIEW_GRADE_WORK_LINKED_TRAINING = 'ORGANIZATION_VIEW_GRADE_WORKLINKED_TRAINING';
    const VIEW_EVALUATION_WORK_LINKED_TRAINING = 'ORGANIZATION_VIEW_EVALUATION_WORKLINKED_TRAINING';
    const MANAGE_COMPANIES = 'ORGANIZATION_MANAGE_COMPANIES';

    const WLT_GROUP_TUTOR = 'ORGANIZATION_WLT_GROUP_TUTOR';
    const WLT_WORK_TUTOR = 'ORGANIZATION_WLT_WORK_TUTOR';
    const DEPARTMENT_HEAD = 'ORGANIZATION_DEPARTMENT_HEAD';
    const WLT_STUDENT = 'ORGANIZATION_WLT_STUDENT';
    const WLT_TEACHER = 'ORGANIZATION_WLT_TEACHER';
    const WLT_MANAGER = 'ORGANIZATION_WLT_MANAGER';
    const WLT_EDUCATIONAL_TUTOR = 'ORGANIZATION_WLT_EDUCATIONAL_TUTOR';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var RoleRepository */
    private $roleRepository;

    /** @var TrainingRepository */
    private $trainingRepository;

    /** @var TeachingRepository */
    private $teachingRepository;

    /** @var AgreementRepository */
    private $agreementRepository;

    /** @var GroupRepository */
    private $groupRepository;

    /** @var TeacherRepository */
    private $teacherRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        RoleRepository $roleRepository,
        TrainingRepository $trainingRepository,
        TeachingRepository $teachingRepository,
        AgreementRepository $agreementRepository,
        TeacherRepository $teacherRepository,
        GroupRepository $groupRepository
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->roleRepository = $roleRepository;
        $this->trainingRepository = $trainingRepository;
        $this->teachingRepository = $teachingRepository;
        $this->agreementRepository = $agreementRepository;
        $this->teacherRepository = $teacherRepository;
        $this->groupRepository = $groupRepository;
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
            self::MANAGE,
            self::ACCESS,
            self::LOCAL_MANAGE,
            self::ACCESS_TRAININGS,
            self::ACCESS_WORK_LINKED_TRAINING,
            self::MANAGE_WORK_LINKED_TRAINING,
            self::GRADE_WORK_LINKED_TRAINING,
            self::VIEW_GRADE_WORK_LINKED_TRAINING,
            self::VIEW_EVALUATION_WORK_LINKED_TRAINING,
            self::MANAGE_COMPANIES,
            self::WLT_WORK_TUTOR,
            self::WLT_GROUP_TUTOR,
            self::DEPARTMENT_HEAD,
            self::WLT_STUDENT,
            self::WLT_TEACHER,
            self::WLT_MANAGER,
            self::WLT_EDUCATIONAL_TUTOR
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
        if ($attribute !== self::LOCAL_MANAGE && $this->decisionManager->decide($token, [self::LOCAL_MANAGE], $subject)
            ) {
            return true;
        }

        switch ($attribute) {
            case self::LOCAL_MANAGE:
                return $this->roleRepository->
                    personHasRole($subject, $user->getPerson(), Role::ROLE_LOCAL_ADMIN);

            // acceder a las enseñanzas del centro y a la gestión de empresas
            case self::MANAGE_COMPANIES:
            case self::ACCESS_TRAININGS:
            case self::MANAGE_WORK_LINKED_TRAINING:
                // Si es jefe de algún departamento o coordinador de FP dual, permitir acceder
                // 1) Jefe de departamento
                if ($this->decisionManager->decide($token, [self::DEPARTMENT_HEAD], $subject)) {
                    return true;
                }

                // 2) Coordinador de FP dual
                return $this->decisionManager->decide($token, [self::WLT_MANAGER], $subject);

            case self::VIEW_GRADE_WORK_LINKED_TRAINING:
            case self::VIEW_EVALUATION_WORK_LINKED_TRAINING:
            case self::GRADE_WORK_LINKED_TRAINING:
            case self::ACCESS_WORK_LINKED_TRAINING:
                // pueden acceder:
                // 1) los que gestionan la FP dual,
                // 2) los profesores que imparten en dual,
                // 3) los tutores de grupo duales,
                // 4) los jefes de departamento,
                // 5) los tutores laborales de los acuerdos de colaboración y
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
                if ($this->decisionManager->decide($token, [self::DEPARTMENT_HEAD], $subject)) {
                    return true;
                }

                // el tutor laboral no puede ver la evaluación numérica
                if ($attribute === self::VIEW_GRADE_WORK_LINKED_TRAINING) {
                    return false;
                }

                // 5) Tutores laborales
                if ($this->decisionManager->decide($token, [self::WLT_WORK_TUTOR], $subject)) {
                    return true;
                }

                // 6) Docentes de dual, salvo que sea realizar evaluación
                if ($attribute !== self::GRADE_WORK_LINKED_TRAINING &&
                    $this->decisionManager->decide($token, [self::WLT_TEACHER], $subject)) {
                    return true;
                }

                // 7) Alumnado con acuerdos, sólo si es acceso
                return $attribute === self::ACCESS_WORK_LINKED_TRAINING &&
                    $this->decisionManager->decide($token, [self::WLT_STUDENT], $subject);

            case self::ACCESS:
                // Si es permiso de acceso, comprobar que pertenece actualmente a la organización
                if ($attribute === self::ACCESS) {
                    $date = new \DateTime();
                    /** @var Membership $membership */
                    foreach ($user->getMemberships() as $membership) {
                        if ($membership->getOrganization() === $subject && $membership->getValidFrom() <= $date &&
                            ($membership->getValidUntil() === null || $membership->getValidUntil() >= $date)) {
                            return true;
                        }
                    }
                }
                break;
            case self::WLT_MANAGER:
                return $this->roleRepository->personHasRole($subject, $user->getPerson(), Role::ROLE_WLT_MANAGER);

            case self::WLT_GROUP_TUTOR:
                $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson(
                    $subject->getCurrentAcademicYear(),
                    $user->getPerson()
                );

                return $teacher &&
                    $this->groupRepository->countAcademicYearAndWltTutor(
                        $subject->getCurrentAcademicYear(),
                        $teacher
                    ) > 0;

            case self::WLT_STUDENT:
                return
                    $this->agreementRepository->countAcademicYearAndStudent(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WLT_WORK_TUTOR:
                return
                    $this->agreementRepository->countAcademicYearAndWorkTutor(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::DEPARTMENT_HEAD:
                return
                    $this->trainingRepository->countAcademicYearAndDepartmentHead(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WLT_TEACHER:
                return
                    $this->teachingRepository->countAcademicYearAndWltPerson(
                        $subject->getCurrentAcademicYear(),
                        $user->getPerson()
                    ) > 0;

            case self::WLT_EDUCATIONAL_TUTOR:
                $teacher = $this->teacherRepository->findOneByAcademicYearAndPerson(
                    $subject->getCurrentAcademicYear(),
                    $user->getPerson()
                );

                return $teacher && $teacher->isWltEducationalTutor();
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
