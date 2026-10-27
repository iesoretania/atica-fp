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

namespace App\Listener;

use App\Entity\Organization;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Impide acceder a datos de una organización distinta a la seleccionada en la sesión.
 *
 * Los controladores reciben las entidades directamente de la URL (por ejemplo, un curso académico
 * o un convenio) y los Voters comprueban los permisos sobre la organización actual, no sobre la
 * del objeto solicitado. Esta comprobación centralizada verifica que cualquier entidad recibida
 * como argumento pertenezca a la organización actual, salvo para los administradores globales.
 */
class OrganizationOwnershipSubscriber implements EventSubscriberInterface
{
    /**
     * Métodos para ascender desde una entidad hasta la organización a la que pertenece, por orden
     * de preferencia. Las entidades sin ninguno de ellos (por ejemplo, las empresas) no se comprueban.
     */
    private const PARENT_GETTERS = [
        'getOrganization',
        'getAcademicYear',
        'getTraining',
        'getGrade',
        'getGroup',
        'getSubject',
        'getLearningOutcome',
        'getTeacher',
        'getStudentEnrollment',
        'getShift',
        'getAgreement',
        'getAgreementEnrollment',
        'getWorkDay',
        'getTrackedWorkDay',
        'getProject',
        'getTrainingProgram',
        'getProgramGrade',
        'getProgramGroup',
        'getStudentProgram',
        'getStudentProgramWorkcenter',
        'getStudentProgramActivity',
        'getAgreementActivityRealization',
        'getActivity',
        'getPerformanceScale',
        'getAnsweredSurvey',
        'getSurvey',
    ];

    private const MAX_DEPTH = 12;

    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    final public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (!$event->isMainRequest() || !$event->getRequest()->hasSession()) {
            return;
        }

        $currentOrganizationId = $event->getRequest()->getSession()->get('organization_id', '');
        if ($currentOrganizationId === '') {
            return;
        }

        $checked = false;
        foreach ($event->getArguments() as $argument) {
            if (!is_object($argument) || $argument instanceof \Symfony\Component\HttpFoundation\Request) {
                continue;
            }
            $organization = $this->findOrganization($argument);
            if (!$organization instanceof Organization || $organization->getId() === (int) $currentOrganizationId) {
                continue;
            }
            if (!$checked) {
                $checked = true;
                // los administradores globales pueden acceder a cualquier organización
                if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
                    return;
                }
            }
            throw new AccessDeniedException('El objeto solicitado pertenece a otra organización.');
        }
    }

    private function findOrganization(object $entity): ?Organization
    {
        $seen = [];
        for ($depth = 0; $depth < self::MAX_DEPTH && is_object($entity); $depth++) {
            if ($entity instanceof Organization) {
                return $entity;
            }
            $id = spl_object_id($entity);
            if (isset($seen[$id])) {
                return null;
            }
            $seen[$id] = true;

            $next = null;
            foreach (self::PARENT_GETTERS as $getter) {
                if (method_exists($entity, $getter) && (new \ReflectionMethod($entity, $getter))->getNumberOfRequiredParameters() === 0) {
                    $next = $entity->$getter();
                    if (is_object($next)) {
                        break;
                    }
                }
            }
            if (!is_object($next)) {
                return null;
            }
            $entity = $next;
        }

        return null;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => 'onKernelControllerArguments'
        ];
    }
}
