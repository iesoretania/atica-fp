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

namespace App\EventSubscriber;

use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\CompanyProgram;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\ItpModule\WorkDay;
use App\Security\ItpModule\TrainingProgramVoter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Plan de formación bloqueado de la FFEOE: sus pantallas de gestión (plan, cursos, grupos, actividades, empresas,
 * alumnado, estancias y calendarios) se consultan en solo lectura para quien no puede modificarlo. La petición se marca
 * con el atributo `_read_only` (que desactiva los formularios y oculta los botones de escritura, ver
 * ReadOnlyFormExtension y la función Twig `is_read_only()`) y cualquier petición que no sea de lectura se deniega.
 */
class LockedTrainingProgramSubscriber implements EventSubscriberInterface
{
    public const ATTRIBUTE = '_read_only';

    /** Controladores de gestión del plan (en App\Controller\ItpModule) */
    private const CONTROLLERS = [
        'TrainingProgramController', 'GradeController', 'GroupController', 'ActivityController',
        'ActivityImportController', 'CompanyController', 'StudentProgramController',
        'StudentProgramWorkcenterController', 'StudentProgramWorkcenterManagerController',
        'StudentProgramWorkcenterCalendarController', 'StudentProgramWorkcenterManagerCalendarController',
    ];

    public function __construct(private readonly AuthorizationCheckerInterface $authorizationChecker)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::CONTROLLER_ARGUMENTS => 'onControllerArguments'];
    }

    public function onControllerArguments(ControllerArgumentsEvent $event): void
    {
        $controller = $event->getController();
        $class = is_array($controller) ? $controller[0]::class : (is_object($controller) ? $controller::class : '');
        if (!str_starts_with($class, 'App\\Controller\\ItpModule\\')
            || !in_array(substr($class, strlen('App\\Controller\\ItpModule\\')), self::CONTROLLERS, true)) {
            return;
        }

        $trainingProgram = null;
        foreach ($event->getArguments() as $argument) {
            $trainingProgram = $this->trainingProgramOf($argument);
            if ($trainingProgram instanceof TrainingProgram) {
                break;
            }
        }
        if (!$trainingProgram instanceof TrainingProgram || $trainingProgram->getId() === null) {
            return;
        }

        if ($this->authorizationChecker->isGranted(TrainingProgramVoter::MODIFY, $trainingProgram)) {
            return;
        }

        $request = $event->getRequest();
        $request->attributes->set(self::ATTRIBUTE, true);
        if (!$request->isMethodSafe()) {
            throw new AccessDeniedHttpException('El plan de formación está bloqueado.');
        }
    }

    private function trainingProgramOf(mixed $subject): ?TrainingProgram
    {
        return match (true) {
            $subject instanceof TrainingProgram => $subject,
            $subject instanceof ProgramGrade => $subject->getTrainingProgram(),
            $subject instanceof Activity, $subject instanceof CompanyProgram, $subject instanceof ProgramGroup
                => $subject->getProgramGrade()?->getTrainingProgram(),
            $subject instanceof StudentProgram => $subject->getProgramGroup()?->getProgramGrade()?->getTrainingProgram(),
            $subject instanceof StudentProgramWorkcenter
                => $subject->getStudentProgram()?->getProgramGroup()?->getProgramGrade()?->getTrainingProgram(),
            $subject instanceof WorkDay => $this->trainingProgramOf($subject->getStudentProgramWorkcenter()),
            default => null,
        };
    }
}
