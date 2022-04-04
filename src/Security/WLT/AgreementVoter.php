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

namespace App\Security\WLT;

use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WLT\Agreement;
use App\Security\CachedVoter;
use App\Security\Edu\GroupVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AgreementVoter extends CachedVoter
{
    public const MANAGE = 'WLT_AGREEMENT_MANAGE';
    public const ACCESS = 'WLT_AGREEMENT_ACCESS';
    public const ATTENDANCE = 'WLT_AGREEMENT_ATTENDANCE';
    public const LOCK = 'WLT_AGREEMENT_LOCK';
    public const GRADE = 'WLT_AGREEMENT_GRADE';
    public const VIEW_GRADE = 'WLT_AGREEMENT_VIEW_GRADE';
    public const VIEW_STUDENT_SURVEY = 'WLT_AGREEMENT_VIEW_STUDENT_SURVEY';
    public const FILL_STUDENT_SURVEY = 'WLT_AGREEMENT_FILL_STUDENT_SURVEY';
    public const VIEW_COMPANY_SURVEY = 'WLT_AGREEMENT_VIEW_COMPANY_SURVEY';
    public const FILL_COMPANY_SURVEY = 'WLT_AGREEMENT_FILL_COMPANY_SURVEY';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var UserExtensionService */
    private $userExtensionService;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        UserExtensionService $userExtensionService
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->userExtensionService = $userExtensionService;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Agreement) {
            return false;
        }
        return in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
            self::ATTENDANCE,
            self::LOCK,
            self::GRADE,
            self::VIEW_GRADE,
            self::VIEW_STUDENT_SURVEY,
            self::FILL_STUDENT_SURVEY,
            self::VIEW_COMPANY_SURVEY,
            self::FILL_COMPANY_SURVEY
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof Agreement) {
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

        $organization = $this->userExtensionService->getCurrentOrganization();

        // si es de otra organización, denegar
        if ($organization !== $this->userExtensionService->getCurrentOrganization()) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $person = $user;

        $isDepartmentHead = false;
        $isGroupTutor = false;
        $isStudent = false;
        $isTeacher = false;
        $isWltManager = false;
        $academicYearIsCurrent = true;

        // Coordinador de FP dual, autorizado salvo modificar si el acuerdo es de otro curso académico
        if ($subject->getProject()->getManager() === $person) {
            $isWltManager = true;
        }

        // Tutor laboral y de seguimiento
        $isWorkTutor = ($user === $subject->getWorkTutor() || $user === $subject->getAdditionalWorkTutor());
        $isEducationalTutor =
            ($subject->getEducationalTutor() && $subject->getEducationalTutor()->getPerson() === $person) ||
            (
                $subject->getAdditionalEducationalTutor() &&
                $subject->getAdditionalEducationalTutor()->getPerson() === $person
            );

        // hay estudiante asociado (puede que no lo haya si es un convenio nuevo)
        if ($subject->getStudentEnrollment()) {
            // Si es jefe de su departamento o coordinador de FP dual, permitir acceder siempre
            // Jefe del departamento del estudiante, autorizado salvo modificar si el acuerdo es de otro curso académico
            $training = $subject->getStudentEnrollment()->getGroup()->getGrade()->getTraining();
            if ($training && $training->getDepartment() && $training->getDepartment()->getHead() &&
                $training->getDepartment()->getHead()->getPerson() === $person) {
                $isDepartmentHead = true;
            }

            // Otros casos: ver qué permisos tiene el usuario

            // Tutor del grupo del acuerdo
            $tutors = $subject->getStudentEnrollment()->getGroup()->getTutors();
            foreach ($tutors as $tutor) {
                if ($tutor->getPerson() === $user) {
                    $isGroupTutor = true;
                    break;
                }
            }

            // Estudiante del acuerdo
            $isStudent = $user === $subject->getStudentEnrollment()->getPerson();

            // Docente del grupo del acuerdo
            $isTeacher = $this->decisionManager->decide(
                $token,
                [GroupVoter::TEACH],
                $subject->getStudentEnrollment()->getGroup()
            );

            $academicYearIsCurrent = $organization->getCurrentAcademicYear() === $training->getAcademicYear();
        }

        switch ($attribute) {
            case self::MANAGE:
                if ($isDepartmentHead || $isWltManager || $isEducationalTutor) {
                    return $academicYearIsCurrent;
                }
                return false;

            // Si es permiso de acceso, comprobar si es el estudiante, docente, el tutor de grupo o
            // el responsable laboral
            case self::ACCESS:
                return $isDepartmentHead || $isWltManager || $isEducationalTutor
                    || $isStudent || $isTeacher || $isWorkTutor || $isGroupTutor;

            // Si es permiso para ver la evaluación, el profesorado del grupo, el tutor o el responsable laboral
            case self::VIEW_GRADE:
                return $isDepartmentHead || $isWltManager || $isEducationalTutor
                    || $isTeacher || $isWorkTutor || $isGroupTutor;

            // Si es permiso para pasar lista, evaluar o revisar la encuesta de empresa
            // puede hacerlo el tutor de grupo o el responsable laboral
            case self::VIEW_COMPANY_SURVEY:
                return $isDepartmentHead || $isWltManager || $isEducationalTutor || $isWorkTutor || $isGroupTutor;
            case self::ATTENDANCE:
            case self::GRADE:
                return $academicYearIsCurrent && ($isDepartmentHead || $isWltManager || $isEducationalTutor
                    || $isWorkTutor || $isGroupTutor);

            // Si es permiso para bloquear/desbloquear jornadas, el tutor de grupo
            case self::LOCK:
                return $academicYearIsCurrent && ($isDepartmentHead || $isWltManager || $isEducationalTutor
                    || $isGroupTutor);

            case self::VIEW_STUDENT_SURVEY:
                return $isDepartmentHead || $isWltManager || $isEducationalTutor || $isStudent || $isGroupTutor;

            case self::FILL_STUDENT_SURVEY:
                $wltStudentSurvey = $subject->getProject()->getStudentSurvey();
                return $academicYearIsCurrent
                    && ($isDepartmentHead || $isWltManager || $isEducationalTutor || $isStudent || $isGroupTutor)
                    && $this->checkSurvey($wltStudentSurvey);

            case self::FILL_COMPANY_SURVEY:
                $wltCompanySurvey = $subject->getProject()->getCompanySurvey();
                return $academicYearIsCurrent
                    && ($isDepartmentHead || $isWltManager || $isEducationalTutor || $isWorkTutor || $isGroupTutor)
                    && $this->checkSurvey($wltCompanySurvey);
        }

        // denegamos en cualquier otro caso
        return false;
    }

    /**
     * @param Survey $wltCompanySurvey
     * @return bool
     * @throws \Exception
     */
    private function checkSurvey(Survey $wltCompanySurvey = null)
    {
        $now = new \DateTime();

        if ($wltCompanySurvey === null) {
            return false;
        }
        if ($wltCompanySurvey->getStartTimestamp() && $wltCompanySurvey->getStartTimestamp() > $now) {
            return false;
        }
        return !($wltCompanySurvey->getEndTimestamp() && $wltCompanySurvey->getEndTimestamp() < $now);
    }
}
