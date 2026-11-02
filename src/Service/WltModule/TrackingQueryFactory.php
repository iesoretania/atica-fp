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

declare(strict_types=1);

namespace App\Service\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Entity\WltModule\Agreement;
use App\Repository\WltModule\GroupRepository;
use App\Repository\WltModule\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Consulta del seguimiento de convenios de FP dual con el alcance de cada persona. La comparten el listado de
 * seguimiento y el escritorio de la página de inicio, para que ambos cuenten exactamente lo mismo.
 */
class TrackingQueryFactory
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupRepository $groupRepository,
        private readonly ProjectRepository $projectRepository,
    ) {
    }

    /**
     * @param bool $isManager    dirección o administración del centro
     * @param bool $isWltManager gestión de la FP dual (responsable de proyectos)
     */
    public function create(AcademicYear $academicYear, Person $person, bool $isManager, bool $isWltManager, string $q = ''): QueryBuilder
    {
        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('a', 'pro', 'w', 'c', 'se', 'p', 'g')
            ->addSelect('SUM(wd.hours)')
            ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence != 0 THEN 1 ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN 1 ELSE 0 END)')
            ->from(Agreement::class, 'a')
            ->leftJoin('a.workDays', 'wd')
            ->join('a.project', 'pro')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('a.workTutor', 'wt')
            ->leftJoin('a.additionalWorkTutor', 'awt')
            ->join('a.educationalTutor', 'et')
            ->leftJoin('a.additionalEducationalTutor', 'aet')
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name');

        if ($q !== '') {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->orWhere('wt.uniqueIdentifier LIKE :tq')
                ->orWhere('awt.firstName LIKE :tq')
                ->orWhere('awt.lastName LIKE :tq')
                ->orWhere('awt.uniqueIdentifier LIKE :tq')
                ->orWhere('pro.name LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }

        $groups = [];
        $projects = [];
        if (!$isWltManager && !$isManager) {
            // jefatura de departamento, docente o tutoría de grupo: los convenios del alumnado de sus grupos
            $groups = $this->groupRepository->findByAcademicYearAndGroupTutorOrTeacherOrDepartmentHeadPerson($academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $this->projectRepository->findByManager($person);
        }

        // se ven siempre los propios
        $own = 'se.person = :person OR a.workTutor = :person OR et.person = :person OR a.additionalWorkTutor = :person OR aet.person = :person';
        if ($groups) {
            $queryBuilder->andWhere('se.group IN (:groups) OR ' . $own)
                ->setParameter('groups', $groups)
                ->setParameter('person', $person);
        }
        if ($projects) {
            $queryBuilder->andWhere('pro IN (:projects) OR ' . $own)
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }
        if (!$isWltManager && !$isManager && !$projects && !$groups) {
            $queryBuilder->andWhere($own)->setParameter('person', $person);
        }

        return $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);
    }
}
