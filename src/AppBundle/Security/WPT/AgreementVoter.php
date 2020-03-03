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

use AppBundle\Entity\Survey;
use AppBundle\Entity\User;
use AppBundle\Entity\WPT\Agreement;
use AppBundle\Security\CachedVoter;
use AppBundle\Security\OrganizationVoter;
use AppBundle\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class AgreementVoter extends CachedVoter
{
    const MANAGE = 'WPT_AGREEMENT_MANAGE';
    const ACCESS = 'WPT_AGREEMENT_ACCESS';
    const ATTENDANCE = 'WPT_AGREEMENT_ATTENDANCE';
    const LOCK = 'WPT_AGREEMENT_LOCK';
    const FILL_REPORT = 'WPT_AGREEMENT_FILL_REPORT';
    const VIEW_REPORT = 'WPT_AGREEMENT_VIEW_REPORT';
    const VIEW_STUDENT_SURVEY = 'WPT_AGREEMENT_VIEW_STUDENT_SURVEY';
    const FILL_STUDENT_SURVEY = 'WPT_AGREEMENT_FILL_STUDENT_SURVEY';
    const VIEW_COMPANY_SURVEY = 'WPT_AGREEMENT_VIEW_COMPANY_SURVEY';
    const FILL_COMPANY_SURVEY = 'WPT_AGREEMENT_FILL_COMPANY_SURVEY';

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
        if (!in_array($attribute, [
            self::MANAGE,
            self::ACCESS,
            self::ATTENDANCE,
            self::LOCK,
            self::FILL_REPORT,
            self::VIEW_REPORT,
            self::VIEW_STUDENT_SURVEY,
            self::FILL_STUDENT_SURVEY,
            self::VIEW_COMPANY_SURVEY,
            self::FILL_COMPANY_SURVEY
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
        if (!$subject instanceof Agreement) {
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

        $organization = $this->userExtensionService->getCurrentOrganization();

        // si es de otra organización, denegar
        if ($organization !== $this->userExtensionService->getCurrentOrganization()) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $person = $user->getPerson();

        $isDepartmentHead = false;
        $isGroupTutor = false;
        $isStudent = false;
        $academicYearIsCurrent = true;

        // Tutor laboral y de seguimiento
        $isWorkTutor = $subject->getWorkTutor() && $user === $subject->getWorkTutor()->getUser();
        $isEducationalTutor =
            $subject->getEducationalTutor() && $subject->getEducationalTutor()->getPerson() === $person;

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
                if ($tutor->getPerson()->getUser() === $user) {
                    $isGroupTutor = true;
                    break;
                }
            }

            // Estudiante del acuerdo
            $isStudent = $user === $subject->getStudentEnrollment()->getPerson()->getUser();

            $academicYearIsCurrent = $organization->getCurrentAcademicYear() === $training->getAcademicYear();
        }

        switch ($attribute) {
            case self::MANAGE:
                if ($isDepartmentHead || $isEducationalTutor) {
                    return $academicYearIsCurrent;
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
                return $academicYearIsCurrent && ($isDepartmentHead || $isEducationalTutor
                    || $isWorkTutor || $isGroupTutor);

            // Si es permiso para bloquear/desbloquear jornadas, el tutor de grupo
            case self::LOCK:
                return $academicYearIsCurrent && ($isDepartmentHead || $isEducationalTutor
                    || $isGroupTutor);

            case self::VIEW_STUDENT_SURVEY:
                return $isDepartmentHead || $isEducationalTutor || $isStudent || $isGroupTutor;

            case self::FILL_STUDENT_SURVEY:
                $studentSurvey = $subject->getProject()->getStudentSurvey();
                return $academicYearIsCurrent
                    && ($isDepartmentHead || $isEducationalTutor || $isStudent || $isGroupTutor)
                    && $this->checkSurvey($studentSurvey);

            case self::FILL_COMPANY_SURVEY:
                $companySurvey = $subject->getProject()->getCompanySurvey();
                return $academicYearIsCurrent
                    && ($isDepartmentHead || $isEducationalTutor || $isWorkTutor || $isGroupTutor)
                    && $this->checkSurvey($companySurvey);
        }

        // denegamos en cualquier otro caso
        return false;
    }

    /**
     * @param Survey $survey
     * @return bool
     * @throws \Exception
     */
    private function checkSurvey(Survey $survey)
    {
        $now = new \DateTime();

        if (!$survey) {
            return false;
        }
        if ($survey->getStartTimestamp() && $survey->getStartTimestamp() > $now) {
            return false;
        }
        if ($survey->getEndTimestamp() && $survey->getEndTimestamp() < $now) {
            return false;
        }
        return true;
    }
}
