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
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivityComment;
use App\Entity\Person;
use App\Security\CachedVoter;
use App\Security\Edu\GroupVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class StudentProgramWorkcenterActivityCommentVoter extends CachedVoter
{
    public const DELETE = 'ITP_STUDENT_PROGRAM_WORKCENTER_ACTIVITY_COMMENT_DELETE';
    public const ACCESS = 'ITP_STUDENT_PROGRAM_WORKCENTER_ACTIVITY_COMMENT_ACCESS';

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
        if (!$subject instanceof StudentProgramWorkcenterActivityComment) {
            return false;
        }
        return in_array($attribute, [
            self::DELETE,
            self::ACCESS
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof StudentProgramWorkcenterActivityComment) {
            return false;
        }

        /** @var Person $user */
        $user = $token->getUser();

        if (!$user instanceof Person) {
            // si el usuario no ha entrado, denegar
            return false;
        }

        $studentProgramWorkcenterActivity = $subject->getStudentProgramActivity();

        $organization = $this->userExtensionService->getCurrentOrganization();

        // si es de otra organización, denegar
        if ($organization !== $this->userExtensionService->getCurrentOrganization()) {
            return false;
        }

        // si el módulo está deshabilitado, denegar
        if (!$organization->getCurrentAcademicYear() instanceof AcademicYear ||
            !$organization->getCurrentAcademicYear()->hasModule('itp')) {
            return false;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        // los administradores globales siempre tienen permiso
        if ($this->decisionManager->decide($token, ['ROLE_ADMIN'])) {
            return true;
        }

        $training = $subject->getStudentProgramActivity()?->getStudentProgramWorkcenter()?->getStudentProgram()?->getProgramGroup()?->getProgramGrade()?->getTrainingProgram()
            ?->getTraining();
        assert($training instanceof Training);

        $academicYear = $training->getAcademicYear();

        // Si la enseñanza no es de la organización actual, denegar
        if ($academicYear?->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        // El jefe de departamento de la familia profesional de proyecto también puede
        $isDepartmentHead = $training?->getDepartment()?->getHead()?->getPerson() === $user;

        $person = $user;

        $studentProgramWorkcenter = $subject->getStudentProgramActivity()?->getStudentProgramWorkcenter();
        assert($studentProgramWorkcenter instanceof StudentProgramWorkcenter);

        $isDepartmentHead = false;
        $isGroupTutor = false;
        $isTeacher = false;
        $academicYearIsCurrent = false;

        // Tutor laboral y de seguimiento
        $isWorkTutor = ($user === $studentProgramWorkcenter->getWorkTutor() || $user === $studentProgramWorkcenter->getAdditionalWorkTutor());
        $isEducationalTutor =
            ($studentProgramWorkcenter->getEducationalTutor() && $studentProgramWorkcenter->getEducationalTutor()->getPerson() === $person) ||
            (
                $studentProgramWorkcenter->getAdditionalEducationalTutor() &&
                $studentProgramWorkcenter->getAdditionalEducationalTutor()->getPerson() === $person
            );


        // hay estudiante asociado (puede que no lo haya si es un convenio nuevo)
        if ($studentProgramWorkcenter->getStudentProgram()?->getStudentEnrollment() instanceof StudentEnrollment) {
            // Si es jefe de su departamento o coordinador de FP dual, permitir acceder siempre
            // Jefe del departamento del estudiante, autorizado salvo modificar si el acuerdo es de otro curso académico
            if ($studentProgramWorkcenter->getStudentProgram()?->getStudentEnrollment()?->getGroup()?->getGrade()
                    ?->getTraining()?->getDepartment()?->getHead()?->getPerson() === $person) {
                $isDepartmentHead = true;
            }

            // Otros casos: ver qué permisos tiene el usuario

            // Tutor dual de grupo
            $managers = $studentProgramWorkcenter?->getStudentProgram()?->getProgramGroup()?->getManagers();
            foreach ($managers ?? [] as $manager) {
                if ($manager->getPerson() === $user) {
                    $isManager = true;
                    break;
                }
            }

            // Tutor del grupo del acuerdo
            $group = $studentProgramWorkcenter?->getStudentProgram()?->getProgramGroup()?->getGroup();
            $tutors = $group?->getTutors();
            foreach ($tutors ?? [] as $tutor) {
                if ($tutor->getPerson() === $user) {
                    $isGroupTutor = true;
                    break;
                }
            }

            // Docente del grupo del acuerdo
            $isTeacher = $this->decisionManager->decide(
                $token,
                [GroupVoter::TEACH],
                $group
            );

            $academicYearIsCurrent = $organization->getCurrentAcademicYear() === $training->getAcademicYear();
        }

        switch ($attribute) {
            case self::DELETE:
                // Pueden borrar todos los comentarios el jefe/a de departamento, el coordinador del proyecto,
                // el responsable de seguimiento o el autor original del comentario
                if ($isDepartmentHead || $isManager || $isEducationalTutor || $subject->getPerson() === $person) {
                    return $academicYearIsCurrent;
                }
                return false;

            // Si es permiso de acceso, comprobar si es docente del grupo, el tutor de grupo o
            // el responsable de seguimiento o laboral
            case self::ACCESS:
                return $isDepartmentHead || $isManager || $isEducationalTutor
                    || $isTeacher || $isWorkTutor || $isGroupTutor;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
