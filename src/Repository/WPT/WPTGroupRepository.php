<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Repository\WPT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Group;
use App\Entity\Person;
use App\Entity\WPT\Shift;
use App\Repository\Edu\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;

class WPTGroupRepository extends GroupRepository
{
    public function findByAcademicYearAndWPTGroupTutorOrDepartmentHeadPerson(AcademicYear $academicYear, Person $person): ArrayCollection
    {
        $groups = new ArrayCollection();

        // grupos de las familias profesionales donde es jefe/a de departamento
        $newGroups = $this->findByAcademicYearAndWPTDepartmentHeadPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        // grupos donde es tutor
        $newGroups = $this->findByAcademicYearAndWPTGroupTutorPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        return $groups;
    }

    private function findByAcademicYearAndWPTDepartmentHeadQueryBuilder(AcademicYear $academicYear, Person $person)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->join('g.grade', 'gr')
            ->join(Shift::class, 's', 'WITH', 's.subject MEMBER OF gr.subjects')
            ->join('gr.training', 'tr')
            ->join('tr.department', 'd')
            ->join('d.head', 'te')
            ->where('tr.academicYear = :academic_year')
            ->andWhere('te.person = :person')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $person)
            ->addOrderBy('g.name');
    }

    public function findByAcademicYearAndWPTDepartmentHeadPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWPTDepartmentHeadQueryBuilder($academicYear, $person)
            ->distinct(true)
            ->getQuery()
            ->getResult();
    }

    public function countAcademicYearAndWPTDepartmentHeadPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWPTDepartmentHeadQueryBuilder($academicYear, $person)
            ->select('COUNT(DISTINCT g)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function findByAcademicYearAndWPTGroupTutorPersonQueryBuilder(AcademicYear $academicYear, Person $person)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->join('g.grade', 'gr')
            ->join(Shift::class, 's', 'WITH', 's.subject MEMBER OF gr.subjects')
            ->join('g.tutors', 'te')
            ->join('gr.training', 'tr')
            ->where('tr.academicYear = :academic_year')
            ->andWhere('te.person = :person')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $person)
            ->addOrderBy('g.name');
    }

    public function findByAcademicYearAndWPTGroupTutorPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWPTGroupTutorPersonQueryBuilder($academicYear, $person)
            ->distinct(true)
            ->getQuery()
            ->getResult();
    }

    public function countAcademicYearAndWPTGroupTutorPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWPTGroupTutorPersonQueryBuilder($academicYear, $person)
            ->select('COUNT(DISTINCT g)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function appendGroups(ArrayCollection $groups, $newGroups): void
    {
        foreach ($newGroups as $group) {
            if (!$groups->contains($group)) {
                $groups->add($group);
            }
        }
    }
}
