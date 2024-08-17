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

use App\Entity\Edu\StudentEnrollment;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WptModule\AgreementEnrollment;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AgreementEnrollmentVoter extends CachedVoter
{
    public const MANAGE = 'WPT_AGREEMENT_ENROLLMENT_MANAGE';
    public const ACCESS = 'WPT_AGREEMENT_ENROLLMENT_ACCESS';
    public const ATTENDANCE = 'WPT_AGREEMENT_ENROLLMENT_ATTENDANCE';
    public const LOCK = 'WPT_AGREEMENT_ENROLLMENT_LOCK';
    public const FILL_REPORT = 'WPT_AGREEMENT_ENROLLMENT_FILL_REPORT';
    public const VIEW_REPORT = 'WPT_AGREEMENT_ENROLLMENT_VIEW_REPORT';
    public const VIEW_STUDENT_SURVEY = 'WPT_AGREEMENT_ENROLLMENT_VIEW_STUDENT_SURVEY';
    public const FILL_STUDENT_SURVEY = 'WPT_AGREEMENT_ENROLLMENT_FILL_STUDENT_SURVEY';
    public const VIEW_COMPANY_SURVEY = 'WPT_AGREEMENT_ENROLLMENT_VIEW_COMPANY_SURVEY';
    public const FILL_COMPANY_SURVEY = 'WPT_AGREEMENT_ENROLLMENT_FILL_COMPANY_SURVEY';
    public const VIEW_ACTIVITY_REPORT = 'WPT_AGREEMENT_ENROLLMENT_VIEW_ACTIVITY_REPORT';

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
        if (!$subject instanceof AgreementEnrollment) {
            return false;
        }

        return in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
            self::ATTENDANCE,
            self::LOCK,
            self::FILL_REPORT,
            self::VIEW_REPORT,
            self::VIEW_STUDENT_SURVEY,
            self::FILL_STUDENT_SURVEY,
            self::VIEW_COMPANY_SURVEY,
            self::FILL_COMPANY_SURVEY,
            self::VIEW_ACTIVITY_REPORT
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof AgreementEnrollment) {
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

        $isDepartmentHead = false;
        $isGroupTutor = false;
        $isStudent = false;
        $agreementIsLocked = true;

        // Tutor laboral y de seguimiento
        $isWorkTutor = $user === $subject->getWorkTutor() || $user === $subject->getAdditionalWorkTutor();
        $isEducationalTutor =
            ($subject->getEducationalTutor() && $subject->getEducationalTutor()->getPerson() === $person)
            || ($subject->getAdditionalEducationalTutor() && $subject->getAdditionalEducationalTutor()->getPerson(
                ) === $person);
        // hay estudiante asociado (puede que no lo haya si es un convenio nuevo)
        if ($subject->getStudentEnrollment() instanceof StudentEnrollment) {
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

            $agreementIsLocked = $organization->getCurrentAcademicYear() !== $training->getAcademicYear();
        }
        $agreementIsLocked = $agreementIsLocked || $subject->getAgreement()->isLocked() || $subject->getAgreement()->getShift()->isLocked();

        switch ($attribute) {
            case self::MANAGE:
                if ($isDepartmentHead || $isEducationalTutor) {
                    return $agreementIsLocked;
                }
                return false;

            // Si es permiso de acceso, comprobar si es el estudiante, docente, el tutor de grupo o
            // el responsable laboral
            case self::ACCESS:
                return $isDepartmentHead || $isEducationalTutor
                    || $isStudent || $isWorkTutor || $isGroupTutor;

            // Si es permiso para ver la evaluación/encuesta de la empresa:
            // El profesorado del grupo, el tutor o el responsable laboral
            case self::VIEW_COMPANY_SURVEY:
            case self::VIEW_REPORT:
                return $isDepartmentHead || $isEducationalTutor
                    || $isWorkTutor || $isGroupTutor;

            case self::ATTENDANCE:
            case self::FILL_REPORT:
                return !$agreementIsLocked && ($isDepartmentHead || $isEducationalTutor
                        || $isWorkTutor || $isGroupTutor);

            // Si es permiso para bloquear/desbloquear jornadas, el tutor de grupo
            case self::LOCK:
                return !$agreementIsLocked && ($isDepartmentHead || $isEducationalTutor
                        || $isGroupTutor);

            case self::VIEW_STUDENT_SURVEY:
                return $isDepartmentHead || $isEducationalTutor || $isStudent || $isGroupTutor;

            case self::FILL_STUDENT_SURVEY:
                $studentSurvey = $subject->getAgreement()->getShift()->getStudentSurvey();
                return !$agreementIsLocked
                    && ($isDepartmentHead || $isEducationalTutor || $isStudent || $isGroupTutor)
                    && $this->checkSurvey($studentSurvey);

            case self::FILL_COMPANY_SURVEY:
                $companySurvey = $subject->getAgreement()->getShift()->getCompanySurvey();
                return !$agreementIsLocked
                    && ($isDepartmentHead || $isEducationalTutor || $isWorkTutor || $isGroupTutor)
                    && $this->checkSurvey($companySurvey);

            case self::VIEW_ACTIVITY_REPORT:
                return $isDepartmentHead || $isEducationalTutor;
        }

        // denegamos en cualquier otro caso
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    private function checkSurvey(Survey $survey = null)
    {
        $now = new \DateTime();

        if (!$survey instanceof Survey) {
            return false;
        }
        if ($survey->getStartTimestamp() && $survey->getStartTimestamp() > $now) {
            return false;
        }
        return !($survey->getEndTimestamp() && $survey->getEndTimestamp() < $now);
    }
}
