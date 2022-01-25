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

namespace App\Repository\WLT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Group;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\WLT\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

class WLTGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findByOrganizationAndAcademicYear(Organization $organization, AcademicYear $academicYear)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->distinct(true)
            ->from(Group::class, 'g')
            ->join(Project::class, 'p', 'WITH', 'g MEMBER OF p.groups')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->where('p.organization = :organization AND tr.academicYear = :academic_year')
            ->setParameter('organization', $organization)
            ->setParameter('academic_year', $academicYear)
            ->addOrderBy('g.name')
            ->getQuery()
            ->getResult();
    }

    private function findByAcademicYearAndWLTTeacherPersonQueryBuilder(AcademicYear $academicYear, Person $person)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->join('g.teachings', 't')
            ->join('t.teacher', 'te')
            ->join(Project::class, 'p', 'WITH', 'g MEMBER OF p.groups')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->where('tr.academicYear = :academic_year')
            ->andWhere('te.person = :person')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $person)
            ->addOrderBy('g.name');
    }

    public function findByAcademicYearAndWLTTeacherPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWLTTeacherPersonQueryBuilder($academicYear, $person)
            ->distinct(true)
            ->getQuery()
            ->getResult();
    }

    public function countAcademicYearAndWLTTeacherPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWLTTeacherPersonQueryBuilder($academicYear, $person)
            ->select('COUNT(DISTINCT g)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function findByAcademicYearAndWLTGroupTutorPersonQueryBuilder(AcademicYear $academicYear, Person $person)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->join('g.tutors', 'te')
            ->join(Project::class, 'p', 'WITH', 'g MEMBER OF p.groups')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->where('tr.academicYear = :academic_year')
            ->andWhere('te.person = :person')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $person)
            ->addOrderBy('g.name');
    }

    public function findByAcademicYearAndWLTGroupTutorPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWLTGroupTutorPersonQueryBuilder($academicYear, $person)
            ->distinct(true)
            ->getQuery()
            ->getResult();
    }

    public function countAcademicYearAndWLTGroupTutorPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWLTGroupTutorPersonQueryBuilder($academicYear, $person)
            ->select('COUNT(DISTINCT g)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function findByAcademicYearAndWLTGroupDepartmentHeadQueryBuilder(AcademicYear $academicYear, Person $person)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->join(Project::class, 'p', 'WITH', 'g MEMBER OF p.groups')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.department', 'd')
            ->join('d.head', 'te')
            ->where('tr.academicYear = :academic_year')
            ->andWhere('te.person = :person')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $person)
            ->addOrderBy('g.name');
    }

    public function findByAcademicYearAndWLTDepartmentHeadPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWLTGroupDepartmentHeadQueryBuilder($academicYear, $person)
            ->distinct(true)
            ->getQuery()
            ->getResult();
    }

    public function countAcademicYearAndWLTDepartmentHeadPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->findByAcademicYearAndWLTGroupDepartmentHeadQueryBuilder($academicYear, $person)
            ->select('COUNT(DISTINCT g)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByAcademicYearAndPerson(AcademicYear $academicYear, Person $person)
    {
        $groups = new ArrayCollection();

        // grupos donde imparte clase
        $newGroups = $this->findByAcademicYearAndWLTTeacherPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        // grupos de las familias profesionales donde es jefe/a de departamento
        $newGroups = $this->findByAcademicYearAndWLTDepartmentHeadPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        // grupos donde es tutor
        $newGroups = $this->findByAcademicYearAndWLTGroupTutorPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        return $groups;
    }

    public function findByOrganizationAndPerson(Organization $organization, Person $person)
    {
        return $this->findByAcademicYearAndPerson($organization->getCurrentAcademicYear(), $person);
    }

    public function findByAcademicYearAndGroupTutorOrDepartmentHeadPerson(AcademicYear $academicYear, Person $person)
    {
        $groups = new ArrayCollection();

        // grupos de las familias profesionales donde es jefe/a de departamento
        $newGroups = $this->findByAcademicYearAndWLTDepartmentHeadPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        // grupos donde es tutor
        $newGroups = $this->findByAcademicYearAndWLTGroupTutorPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        return $groups;
    }

    public function findByAcademicYearAndGroupTutorOrTeacherOrDepartmentHeadPerson(AcademicYear $academicYear, Person $person)
    {
        $groups = new ArrayCollection();

        // grupos de las familias profesionales donde es jefe/a de departamento
        $newGroups = $this->findByAcademicYearAndWLTDepartmentHeadPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        // grupos donde es profesor
        $newGroups = $this->findByAcademicYearAndWLTTeacherPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        // grupos donde es tutor
        $newGroups = $this->findByAcademicYearAndWLTGroupTutorPerson($academicYear, $person);
        $this->appendGroups($groups, $newGroups);

        return $groups;
    }

    private function appendGroups(ArrayCollection $groups, $newGroups)
    {
        foreach ($newGroups as $group) {
            if (false === $groups->contains($group)) {
                $groups->add($group);
            }
        }
    }
}
