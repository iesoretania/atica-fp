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

namespace App\Twig\Components\Dashboard;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use App\Entity\Person;
use App\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Panel del escritorio con indicadores del seguimiento de un módulo (FCT, FFEOE).
 *
 * Los indicadores se calculan sobre la MISMA consulta que usa el listado de seguimiento, de modo que cada persona solo
 * cuenta lo que ya puede ver en él (su alcance según el rol). Cada fila de la consulta es una asignación de estudiante
 * a un centro de trabajo con sus fechas y sus horas totales y bloqueadas.
 */
abstract class AbstractTrackingPanel
{
    /** Días de antelación para avisar de los comienzos próximos. */
    private const UPCOMING_DAYS = 15;

    public function __construct(
        protected readonly Security $security,
        protected readonly UserExtensionService $userExtensionService,
        protected readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** Permiso necesario para ver el seguimiento del módulo. */
    abstract protected function accessAttribute(): string;

    /**
     * Consulta de seguimiento visible para la persona, ya con las uniones del listado.
     * Debe permitir añadir selecciones con los alias de fechas y horas indicados en {@see self::rows()}.
     */
    abstract protected function visibleQueryBuilder(AcademicYear $academicYear, Person $person, bool $isManager): QueryBuilder;

    /** Alias DQL de la fecha de inicio, de la fecha de fin y de las horas totales (SUM) y bloqueadas (SUM). */
    abstract protected function expressions(): array;

    abstract public function listRoute(): string;

    abstract public function module(): string;

    /** @return array{current: int, upcoming: int, pending: int, total: int, year: int|null}|null null si la persona no puede ver el panel */
    public function summary(): ?array
    {
        $organization = $this->userExtensionService->getCurrentOrganization();
        $person = $this->security->getUser();
        if (!$organization instanceof Organization || !$person instanceof Person
            || !$this->security->isGranted($this->accessAttribute(), $organization)) {
            return null;
        }
        $academicYear = $organization->getCurrentAcademicYear();
        if (!$academicYear instanceof AcademicYear) {
            return null;
        }

        [$start, $end, $hours, $lockedHours] = $this->expressions();
        $queryBuilder = $this->visibleQueryBuilder(
            $academicYear,
            $person,
            $this->security->isGranted(\App\Security\OrganizationVoter::MANAGE, $organization)
        );
        $queryBuilder
            ->select($start . ' AS start_date', $end . ' AS end_date', $hours . ' AS hours', $lockedHours . ' AS locked_hours')
            ->resetDQLPart('orderBy');

        $today = new \DateTimeImmutable('today');
        $limit = $today->modify('+' . self::UPCOMING_DAYS . ' days');
        $summary = ['current' => 0, 'upcoming' => 0, 'pending' => 0, 'total' => 0, 'year' => $academicYear->getId()];

        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $from = $row['start_date'] instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($row['start_date']) : null;
            $to = $row['end_date'] instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($row['end_date']) : null;
            $summary['total']++;
            if ($from !== null && $from > $today && $from <= $limit) {
                $summary['upcoming']++;
            }
            if (($from === null || $from <= $today) && ($to === null || $to >= $today)) {
                $summary['current']++;
            }
            if ($to !== null && $to < $today && (int) $row['hours'] > (int) $row['locked_hours']) {
                $summary['pending']++;
            }
        }

        return $summary;
    }
}
