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

use App\Entity\Edu\Group;
use App\Entity\User;
use App\Entity\WPT\Agreement;
use App\Entity\WPT\Visit;
use App\Repository\WPT\AgreementEnrollmentRepository;
use App\Security\CachedVoter;
use App\Security\OrganizationVoter;
use App\Service\UserExtensionService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class VisitVoter extends CachedVoter
{
    const MANAGE = 'WPT_VISIT_MANAGE';
    const ACCESS = 'WPT_VISIT_ACCESS';

    /** @var AccessDecisionManagerInterface */
    private $decisionManager;

    /** @var UserExtensionService */
    private $userExtensionService;

    private $agreementEnrollmentRepository;

    public function __construct(
        CacheItemPoolInterface $cacheItemPoolItemPool,
        AccessDecisionManagerInterface $decisionManager,
        UserExtensionService $userExtensionService,
        AgreementEnrollmentRepository $agreementEnrollmentRepository
    ) {
        parent::__construct($cacheItemPoolItemPool);
        $this->decisionManager = $decisionManager;
        $this->userExtensionService = $userExtensionService;
        $this->agreementEnrollmentRepository = $agreementEnrollmentRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject)
    {

        if (!$subject instanceof Visit) {
            return false;
        }
        if (!in_array($attribute, [
            self::MANAGE,
            self::ACCESS
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
        if (!$subject instanceof Visit) {
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

        // Si no es de la organización actual, denegar
        if ($subject->getTeacher()->getAcademicYear()->getOrganization() !== $organization) {
            return false;
        }

        // Si es administrador de la organización, permitir siempre
        if ($this->decisionManager->decide($token, [OrganizationVoter::MANAGE], $organization)) {
            return true;
        }

        switch ($attribute) {
            case self::MANAGE:
                // El propio docente puede gestionar su visita si es del curso académico actual
                return $subject->getTeacher()->getPerson() === $user->getPerson()
                    && $subject->getTeacher()->getAcademicYear() === $organization->getCurrentAcademicYear();
            case self::ACCESS:
                // Puede acceder a los datos de la visita el propio docente
                if ($subject->getTeacher()->getPerson() === $user->getPerson()) {
                    return true;
                }
                // Puede acceder el jefe/a de departamento
                // de los grupos de los proyectos
                /** @var Agreement $agreement */
                foreach ($subject->getAgreements() as $agreement) {
                    /** @var Group $group */
                    foreach ($agreement->getGroups() as $group) {
                        if ($group->getGrade()->getTraining()->getDepartment() &&
                            $group->getGrade()->getTraining()->getDepartment()->getHead() &&
                            $group->getGrade()->getTraining()->getDepartment()->getHead()->getPerson()
                                === $user->getPerson()) {
                            return true;
                        }
                    }
                }
                // Puede acceder el tutor de los estudiantes visitados
                foreach ($subject->getStudentEnrollments() as $studentEnrollment) {
                    foreach ($studentEnrollment->getGroup()->getTutors() as $tutor) {
                        if ($tutor->getPerson() === $user->getPerson()) {
                            return true;
                        }
                    }
                }
                // Comprobar si el profesor es tutor docente de los acuerdos
                // de colaboración del departamento dirigido por el usuario
                $agreementEnrollments = $this->agreementEnrollmentRepository
                    ->findByEducationalTutor($subject->getTeacher());
                foreach ($agreementEnrollments as $agreementEnrollment) {
                    $training = $agreementEnrollment->getAgreement()->getShift()->getGrade()->getTraining();
                    if ($training->getDepartment()
                        && $training->getDepartment()->getHead()
                        && $training->getDepartment()->getHead()->getPerson() === $user->getPerson()) {
                        return true;
                    }
                }
                return false;
        }

        // denegamos en cualquier otro caso
        return false;
    }
}
