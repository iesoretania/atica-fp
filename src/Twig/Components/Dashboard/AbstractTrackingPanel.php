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
use App\Security\OrganizationVoter as BaseOrganizationVoter;
use App\Service\UserExtensionService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Panel del escritorio con indicadores del seguimiento de un módulo (FCT, FFEOE).
 *
 * Los indicadores se calculan sobre la MISMA consulta que usa el listado de seguimiento, de modo que cada persona solo
 * cuenta lo que ya puede ver en él (su alcance según el rol). Cada fila de la consulta es una asignación de estudiante
 * a un centro de trabajo con sus fechas, sus horas y sus ausencias.
 *
 * Hay dos vistas: la personal del alumnado (sus propias horas y ausencias) y la de equipo para el resto (dirección,
 * jefatura, tutorías y profesorado), con contadores sobre el alumnado de su ámbito.
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

    /** Consulta de seguimiento visible para la persona, ya con las uniones del listado (alias p = persona del alumno). */
    abstract protected function visibleQueryBuilder(AcademicYear $academicYear, Person $person, bool $isManager): QueryBuilder;

    /**
     * Expresiones DQL de la consulta de seguimiento: fechas de inicio y fin, horas totales y bloqueadas (SUM, en
     * centésimas), ausencias y ausencias justificadas (SUM) y, si existe, fecha de firma del convenio (null si no).
     *
     * @return array{start: string, end: string, hours: string, locked: string, absences: string, justified: string, sign: string|null}
     */
    abstract protected function expressions(): array;

    abstract public function listRoute(): string;

    abstract public function module(): string;

    /**
     * @return array{
     *     year: int|null, total: int, own: array{hours: int, locked: int, absences: int, unjustified: int, count: int}|null,
     *     team: array<string, int>|null
     * }|null null si la persona no puede ver el panel
     */
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

        $isManager = $this->security->isGranted(BaseOrganizationVoter::MANAGE, $organization);
        $e = $this->expressions();
        $queryBuilder = $this->visibleQueryBuilder($academicYear, $person, $isManager);
        $queryBuilder
            ->select(
                $e['start'] . ' AS start_date',
                $e['end'] . ' AS end_date',
                $e['hours'] . ' AS hours',
                $e['locked'] . ' AS locked_hours',
                $e['absences'] . ' AS absences',
                $e['justified'] . ' AS justified',
                ($e['sign'] ?? 'p.id') . ' AS sign_date',
                'p.id AS student_id'
            )
            ->addGroupBy('p.id')
            ->resetDQLPart('orderBy');

        $today = new \DateTimeImmutable('today');
        $limit = $today->modify('+' . self::UPCOMING_DAYS . ' days');
        $summary = ['year' => $academicYear->getId(), 'total' => 0, 'own' => null, 'team' => null];
        $team = ['total' => 0, 'current' => 0, 'upcoming' => 0, 'pending' => 0, 'unjustified' => 0, 'unsigned' => 0];

        foreach ($queryBuilder->getQuery()->getArrayResult() as $row) {
            $summary['total']++;
            $absences = (int) $row['absences'];
            $unjustified = max(0, $absences - (int) $row['justified']);

            if ((int) $row['student_id'] === $person->getId()) {
                $own = $summary['own'] ?? ['hours' => 0, 'locked' => 0, 'absences' => 0, 'unjustified' => 0, 'count' => 0];
                $own['hours'] += (int) $row['hours'];
                $own['locked'] += (int) $row['locked_hours'];
                $own['absences'] += $absences;
                $own['unjustified'] += $unjustified;
                $own['count']++;
                $summary['own'] = $own;
                continue;
            }

            $from = $row['start_date'] instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($row['start_date']) : null;
            $to = $row['end_date'] instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($row['end_date']) : null;
            $team['total']++;
            if ($from !== null && $from > $today && $from <= $limit) {
                $team['upcoming']++;
            }
            if (($from === null || $from <= $today) && ($to === null || $to >= $today)) {
                $team['current']++;
            }
            if ($to !== null && $to < $today && (int) $row['hours'] > (int) $row['locked_hours']) {
                $team['pending']++;
            }
            if ($unjustified > 0) {
                $team['unjustified']++;
            }
            if ($e['sign'] !== null && $row['sign_date'] === null) {
                $team['unsigned']++;
            }
        }

        if ($team['total'] > 0) {
            // «sin firmar» solo lo gestionan la dirección y la jefatura
            if (!$isManager || $e['sign'] === null) {
                unset($team['unsigned']);
            }
            $summary['team'] = $team;
        }

        return $summary;
    }
}
