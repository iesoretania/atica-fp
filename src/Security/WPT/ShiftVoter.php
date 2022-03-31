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

use App\Entity\Person;
use App\Entity\WPT\Shift;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class ShiftVoter extends CachedVoter
{
    public const MANAGE = 'WPT_SHIFT_MANAGE';
    public const ACCESS_MANAGER_SURVEY = 'WPT_MANAGER_SURVEY_ACCESS';
    public const FILL_MANAGER_SURVEY = 'WPT_MANAGER_SURVEY_MANAGE';
    public const ACCESS_EDUCATIONAL_TUTOR_SURVEY = 'WPT_EDUCATIONAL_TUTOR_SURVEY_ACCESS';
    public const FILL_EDUCATIONAL_TUTOR_SURVEY = 'WPT_EDUCATIONAL_TUTOR_SURVEY_MANAGE';
    public const REPORT_STUDENT_SURVEY = 'WPT_STUDENT_SURVEY_REPORT';
    public const REPORT_COMPANY_SURVEY = 'WPT_COMPANY_SURVEY_REPORT';
    public const REPORT_MEETING = 'WPT_MEETING_REPORT';
    public const REPORT_ATTENDANCE = 'WPT_ATTENDANCE_REPORT';
    public const REPORT_GRADING = 'WPT_GRADING_REPORT';

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

        if (!$subject instanceof Shift) {
            return false;
        }
        return in_array($attribute, [
            self::MANAGE,
            self::ACCESS_MANAGER_SURVEY,
            self::FILL_MANAGER_SURVEY,
            self::ACCESS_EDUCATIONAL_TUTOR_SURVEY,
            self::FILL_EDUCATIONAL_TUTOR_SURVEY,
            self::REPORT_STUDENT_SURVEY,
            self::REPORT_COMPANY_SURVEY,
            self::REPORT_MEETING,
            self::REPORT_ATTENDANCE,
            self::REPORT_GRADING
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if (!$subject instanceof Shift) {
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

        // Si no es de la organización actual, denegar
        if ($subject->getGrade() && $subject->getGrade()
                ->getTraining()->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $isCurrentAcademicYear = $subject->getGrade()->getTraining()
                ->getAcademicYear() === $this->userExtensionService->getCurrentOrganization();

        // El jefe de departamento de la familia profesional de proyecto también puede
        $isDepartmentHead = $subject->getGrade()->getTraining()->getDepartment() && $subject->getGrade()->getTraining(
            )->getDepartment()->getHead() &&
            $subject->getGrade()->getTraining()->getDepartment()->getHead()->getPerson() === $user;

        switch ($attribute) {
            case self::MANAGE:
            case self::ACCESS_MANAGER_SURVEY:
            case self::REPORT_STUDENT_SURVEY:
            case self::REPORT_COMPANY_SURVEY:
            case self::REPORT_MEETING:
            case self::REPORT_ATTENDANCE:
            case self::REPORT_GRADING:
                return $isDepartmentHead;

            case self::FILL_MANAGER_SURVEY:
                return $isCurrentAcademicYear && $isDepartmentHead;

            case self::ACCESS_EDUCATIONAL_TUTOR_SURVEY:
            case self::FILL_EDUCATIONAL_TUTOR_SURVEY:
                if ($isDepartmentHead) {
                    return true;
                }
                // si es el responsable de seguimiento
                foreach ($subject->getAgreements() as $agreement) {
                    foreach ($agreement->getAgreementEnrollments() as $agreementEnrollment) {
                        if ($agreementEnrollment->getEducationalTutor()->getPerson() === $user ||
                            $agreementEnrollment->getAdditionalEducationalTutor()->getPerson() === $user
                    ) {
                            return true;
                        }
                    }
                }
                return false;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
