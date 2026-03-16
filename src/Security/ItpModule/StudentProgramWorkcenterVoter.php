<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Security\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Person;
use App\Repository\Edu\TeacherRepository;
use App\Repository\ItpModule\ProgramGroupRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class StudentProgramWorkcenterVoter extends CachedVoter
{
    public const MANAGE = 'ITP_STUDENT_PROGRAM_WORKCENTER_MANAGE';
    public const ACCESS = 'ITP_STUDENT_PROGRAM_WORKCENTER_ACCESS';
    public const FILL = 'ITP_STUDENT_PROGRAM_WORKCENTER_FILL';
    public const LOCK = 'ITP_STUDENT_PROGRAM_WORKCENTER_LOCK';
    public const ATTENDANCE = 'ITP_STUDENT_PROGRAM_WORKCENTER_ATTENDANCE';
    public const GRADE = 'ITP_STUDENT_PROGRAM_WORKCENTER_GRADE';
    public const VIEW_GRADE = 'ITP_STUDENT_PROGRAM_WORKCENTER_VIEW_GRADE';
    public const VIEW_EVALUATION = 'ITP_STUDENT_PROGRAM_WORKCENTER_VIEW_EVALUATION';

    public const VIEW_STUDENT_SURVEY = 'ITP_STUDENT_PROGRAM_WORKCENTER_VIEW_STUDENT_SURVEY';
    public const FILL_STUDENT_SURVEY = 'ITP_STUDENT_PROGRAM_WORKCENTER_FILL_STUDENT_SURVEY';
    public const VIEW_WORK_TUTOR_SURVEY = 'ITP_STUDENT_PROGRAM_WORKCENTER_VIEW_WORK_TUTOR_SURVEY';
    public const FILL_WORK_TUTOR_SURVEY = 'ITP_STUDENT_PROGRAM_WORKCENTER_FILL_WORK_TUTOR_SURVEY';
    public const VIEW_EDUCATIONAL_TUTOR_SURVEY = 'ITP_STUDENT_PROGRAM_WORKCENTER_VIEW_EDUCATIONAL_TUTOR_SURVEY';
    public const FILL_EDUCATIONAL_TUTOR_SURVEY = 'ITP_STUDENT_PROGRAM_WORKCENTER_FILL_EDUCATIONAL_TUTOR_SURVEY';

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        private readonly AccessDecisionManagerInterface $decisionManager,
        private readonly TeacherRepository $teacherRepository,
        private readonly ProgramGroupRepository $programGroupRepository,
        private readonly UserExtensionService $userExtensionService
    ) {
        parent::__construct($cacheItemPoolItemPool);
    }

    /**
     * {@inheritdoc}
     */
    final public function supports($attribute, $subject): bool
    {
        if (!$subject instanceof StudentProgramWorkcenter) {
            return false;
        }
        return in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
            self::FILL,
            self::LOCK,
            self::ATTENDANCE,
            self::GRADE,
            self::VIEW_GRADE,
            self::VIEW_EVALUATION,
            self::VIEW_STUDENT_SURVEY,
            self::FILL_STUDENT_SURVEY,
            self::VIEW_WORK_TUTOR_SURVEY,
            self::FILL_WORK_TUTOR_SURVEY,
            self::VIEW_EDUCATIONAL_TUTOR_SURVEY,
            self::FILL_EDUCATIONAL_TUTOR_SURVEY,
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof StudentProgramWorkcenter) {
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
        if (!$organization->getCurrentAcademicYear() instanceof AcademicYear ||
            !$organization->getCurrentAcademicYear()->hasModule('itp')) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $training = $subject->getStudentProgram()?->getProgramGroup()?->getProgramGrade()?->getGrade()
            ?->getTraining();
        $academicYear = $training?->getAcademicYear();

        // Si la enseñanza no es de la organización actual, denegar
        if ($academicYear?->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $teacher = $this->teacherRepository->findOneByPersonAndAcademicYear($user, $academicYear);
        $isItpStudent = $subject->getStudentProgram()->getStudentEnrollment()->getPerson() === $user;
        $isItpManager = false;

        if ($teacher instanceof Teacher) {
            $programGroup = $subject->getStudentProgram()->getProgramGroup();
            foreach ($programGroup->getManagers() as $manager) {
                if ($manager->getPerson() === $user) {
                    $isItpStudent = true;
                    break;
                }
            }
            $isGroupTutor = false;
            foreach ($programGroup->getGroup()->getTutors() as $tutor) {
                if ($tutor->getPerson() === $user) {
                    $isGroupTutor = true;
                    break;
                }
            }
            $isStudentProgramWorkcenterEducationalTutor = $subject->getEducationalTutor() === $teacher || $subject->getAdditionalEducationalTutor() === $teacher;
        } else {
            $isGroupTutor = false;
            $isStudentProgramWorkcenterEducationalTutor = false;
        }
        $isStudentProgramWorkcenterWorkTutor = $subject->getWorkTutor() === $user || $subject->getAdditionalWorkTutor() === $user;

        $isCurrentAcademicYear = $academicYear
            === $this->userExtensionService->getCurrentOrganization()->getCurrentAcademicYear();

        // El jefe de departamento de la familia profesional del ciclo formativo
        $isDepartmentHead = $training?->getDepartment()?->getHead()?->getPerson() === $user;

        switch ($attribute) {
            case self::MANAGE:
                return $isDepartmentHead && $isItpManager;
            case self::VIEW_STUDENT_SURVEY:
            case self::FILL:
                return $isItpStudent || $isDepartmentHead || $isItpManager || $isGroupTutor || $isStudentProgramWorkcenterEducationalTutor;
            case self::ACCESS:
                return $isItpStudent || $isDepartmentHead || $isItpManager || $isGroupTutor || $isStudentProgramWorkcenterEducationalTutor || $isStudentProgramWorkcenterWorkTutor;
            case self::LOCK:
                return $isGroupTutor || $isStudentProgramWorkcenterEducationalTutor || $isDepartmentHead || $isItpManager;
            case self::ATTENDANCE:
            case self::GRADE:
            case self::VIEW_GRADE:
                return $isGroupTutor || $isStudentProgramWorkcenterEducationalTutor || $isDepartmentHead || $isItpManager || $isStudentProgramWorkcenterWorkTutor;
            case self::VIEW_EVALUATION:
                return $isGroupTutor || $isStudentProgramWorkcenterEducationalTutor || $isDepartmentHead || $isItpManager;
            case self::FILL_STUDENT_SURVEY:
                return $isCurrentAcademicYear && ($isItpStudent || $isDepartmentHead || $isItpManager || $isGroupTutor || $isStudentProgramWorkcenterEducationalTutor);
            case self::VIEW_WORK_TUTOR_SURVEY:
                return $isDepartmentHead || $isItpManager || $isGroupTutor || $isStudentProgramWorkcenterEducationalTutor || $isStudentProgramWorkcenterWorkTutor;
            case self::FILL_WORK_TUTOR_SURVEY:
                return $isCurrentAcademicYear && ($isDepartmentHead || $isItpManager || $isGroupTutor || $isStudentProgramWorkcenterEducationalTutor || $isStudentProgramWorkcenterWorkTutor);
            case self::VIEW_EDUCATIONAL_TUTOR_SURVEY:
                return $isDepartmentHead || $isItpManager || $isStudentProgramWorkcenterEducationalTutor;
            case self::FILL_EDUCATIONAL_TUTOR_SURVEY:
                return $isCurrentAcademicYear && ($isDepartmentHead || $isItpManager || $isStudentProgramWorkcenterEducationalTutor);
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
