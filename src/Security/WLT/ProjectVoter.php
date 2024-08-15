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

namespace App\Security\WLT;

use App\Entity\Person;
use App\Entity\WLT\Project;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class ProjectVoter extends CachedVoter
{
    public const MANAGE = 'WLT_PROJECT_MANAGE';
    public const ACCESS_EDUCATIONAL_TUTOR_SURVEY = 'WLT_EDUCATIONAL_TUTOR_SURVEY_ACCESS';
    public const FILL_EDUCATIONAL_TUTOR_SURVEY = 'WLT_EDUCATIONAL_TUTOR_SURVEY_MANAGE';
    public const REPORT_STUDENT_SURVEY = 'WLT_STUDENT_SURVEY_REPORT';
    public const REPORT_COMPANY_SURVEY = 'WLT_COMPANY_SURVEY_REPORT';
    public const REPORT_ORGANIZATION_SURVEY = 'WLT_ORGANIZATION_SURVEY_REPORT';
    public const REPORT_MEETING = 'WLT_MEETING_REPORT';
    public const REPORT_ATTENDANCE = 'WLT_ATTENDANCE_REPORT';
    public const REPORT_GRADING = 'WLT_GRADING_REPORT';
    public const REPORT_LEARNING_PROGRAM = 'WLT_LEARNING_PROGRAM';

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

        if (!$subject instanceof Project) {
            return false;
        }
        return in_array($attribute, [
            self::MANAGE,
            self::ACCESS_EDUCATIONAL_TUTOR_SURVEY,
            self::FILL_EDUCATIONAL_TUTOR_SURVEY,
            self::REPORT_STUDENT_SURVEY,
            self::REPORT_COMPANY_SURVEY,
            self::REPORT_ORGANIZATION_SURVEY,
            self::REPORT_MEETING,
            self::REPORT_ATTENDANCE,
            self::REPORT_GRADING,
            self::REPORT_LEARNING_PROGRAM
        ], true);
    }

    /**
     * {@inheritdoc}
     */
    final public function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        if (!$subject instanceof Project) {
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
        if ($subject->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        $isProjectManager = $subject->getManager() === $user;

        $isCurrentAcademicYear = false;
        foreach ($subject->getGroups() as $group) {
            if ($group->getGrade()->getTraining()->getDepartment() &&
            $group
                ->getGrade()->getTraining()
                ->getAcademicYear() === $this->userExtensionService->getCurrentOrganization()) {
                $isCurrentAcademicYear = true;
                break;
            }
        }

        // El coordinador puede gestionar el proyecto
        if ($isProjectManager) {
            return true;
        }

        // El jefe de departamento de la familia profesional de proyecto también puede
        $isDepartmentHead = false;
        foreach ($subject->getGroups() as $group) {
            if ($group->getGrade()->getTraining()->getDepartment()
                && $group->getGrade()->getTraining()->getDepartment()->getHead()
                && $group
                    ->getGrade()->getTraining()
                    ->getDepartment()->getHead()->getPerson() === $user
            ) {
                $isDepartmentHead = true;
                break;
            }
        }

        switch ($attribute) {
            case self::MANAGE:
            case self::REPORT_STUDENT_SURVEY:
            case self::REPORT_COMPANY_SURVEY:
            case self::REPORT_MEETING:
            case self::REPORT_ATTENDANCE:
            case self::REPORT_GRADING:
            case self::REPORT_LEARNING_PROGRAM:
                return $isProjectManager || $isDepartmentHead;

            case self::REPORT_ORGANIZATION_SURVEY:
                return $isProjectManager;

            case self::ACCESS_EDUCATIONAL_TUTOR_SURVEY:
            case self::FILL_EDUCATIONAL_TUTOR_SURVEY:
                if ($isDepartmentHead) {
                    return true;
                }

                // El responsable de seguimiento de un acuerdo también puede
                foreach ($subject->getAgreements() as $agreement) {
                    if ($agreement->getEducationalTutor()->getPerson() === $user ||
                        (
                            $agreement->getAdditionalEducationalTutor() &&
                            $agreement->getAdditionalEducationalTutor()->getPerson() === $user
                        )
                    ) {
                        return true;
                    }
                }
                return false;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
