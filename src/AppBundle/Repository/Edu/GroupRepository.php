<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Repository\Edu;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Group;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Edu\Teaching;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;

class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findByOrganization(Organization $organization)
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.grade', 'gr')
            ->innerJoin('gr.training', 't')
            ->join('t.academicYear', 'a')
            ->where('a.organization = :organization')
            ->setParameter('organization', $organization)
            ->addOrderBy('a.description', 'DESC')
            ->addOrderBy('g.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @return QueryBuilder
     */
    public function findByAcademicYearQueryBuilder(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.grade', 'gr')
            ->innerJoin('gr.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);
    }

    /**
     * @param AcademicYear $academicYear
     * @return Group[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @param string $internalCode
     * @return Group|null
     */
    public function findOneByAcademicYearAndInternalCode(AcademicYear $academicYear, $internalCode)
    {
        try {
            return $this->findByAcademicYearQueryBuilder($academicYear)
                ->andWhere('g.internalCode = :internal_code')
                ->setParameter('internal_code', $internalCode)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param $items
     * @param AcademicYear $academicYear
     * @return Group[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('g.id IN (:items)')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('g.name')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYearAndWltQueryBuilder(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->andWhere('t.academicYear = :academic_year')
            ->andWhere('t.workLinked = :work_linked')
            ->setParameter('work_linked', true)
            ->setParameter('academic_year', $academicYear);
    }

    public function findByAcademicYearAndWltTutorQueryBuilder(AcademicYear $academicYear, Teacher $teacher)
    {
        return $this->findByAcademicYearAndWltQueryBuilder($academicYear)
            ->andWhere(':tutor MEMBER OF g.tutors')
            ->setParameter('tutor', $teacher);
    }

    public function findByAcademicYearAndWltTutor(AcademicYear $academicYear, Teacher $teacher)
    {
        return $this->findByAcademicYearAndWltTutorQueryBuilder($academicYear, $teacher)
            ->getQuery()
            ->getResult();
    }

    public function countAcademicYearAndWltTutor(AcademicYear $academicYear, Teacher $tutor)
    {
        try {
            return $this->findByAcademicYearAndWltTutorQueryBuilder($academicYear, $tutor)
                ->select('COUNT(g)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return 0;
    }

    public function findByAcademicYearAndWltTeacherQueryBuilder(AcademicYear $academicYear, Teacher $teacher)
    {
        $groups = array_map(function (Teaching $t) {
            return $t->getGroup();
        }, $teacher->getTeachings()->toArray());

        return $this->findByAcademicYearAndWltQueryBuilder($academicYear)
            ->andWhere('g IN (:groups)')
            ->setParameter('groups', $groups);
    }

    public function findByAcademicYearAndWltTeacher(AcademicYear $academicYear, Teacher $teacher)
    {
        return $this->findByAcademicYearAndWltTeacherQueryBuilder($academicYear, $teacher)
            ->getQuery()
            ->getResult();
    }

    public function countAcademicYearAndWltTeacher(AcademicYear $academicYear, Teacher $teacher)
    {
        $groups = array_map(function (Teaching $t) {
            return $t->getGroup();
        }, $teacher->getTeachings()->toArray());

        return $this->findByAcademicYearAndWltQueryBuilder($academicYear)
            ->select('COUNT(g)')
            ->andWhere('g IN (:groups)')
            ->setParameter('groups', $groups)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByAcademicYearAndWltHead(AcademicYear $academicYear, Teacher $teacher)
    {
        return $this->findByAcademicYearAndWltQueryBuilder($academicYear)
            ->join('t.department', 'd')
            ->andWhere('d.head = :teacher')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndTeacherPerson(Organization $organization, Person $person)
    {
        return $this->createQueryBuilder('g')
            ->distinct(true)
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('t.academicYear', 'ay')
            ->join('g.teachings', 'te')
            ->join('te.teacher', 'tea')
            ->andWhere('ay.organization = :organization')
            ->andWhere('tea.person = :person')
            ->setParameter('organization', $organization)
            ->setParameter('person', $person)
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndHeadPerson(Organization $organization, Person $person)
    {
        return $this->createQueryBuilder('g')
            ->distinct(true)
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('t.academicYear', 'ay')
            ->join('t.department', 'd')
            ->join('d.head', 'h')
            ->andWhere('ay.organization = :organization')
            ->andWhere('h.person = :person')
            ->setParameter('organization', $organization)
            ->setParameter('person', $person)
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndGroupTutorPerson(Organization $organization, Person $person)
    {
        return $this->createQueryBuilder('g')
            ->distinct(true)
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('t.academicYear', 'ay')
            ->join('g.tutors', 'tut')
            ->join('tut.teacher', 'tea')
            ->andWhere('ay.organization = :organization')
            ->andWhere('tea.person = :person')
            ->setParameter('organization', $organization)
            ->setParameter('person', $person)
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndPerson(Organization $organization, Person $person)
    {
        $groups = new ArrayCollection();

        // grupos donde imparte clase
        $newGroups = $this->findByOrganizationAndTeacherPerson($organization, $person);
        $this->appendGroups($groups, $newGroups);

        // grupos de las familias profesionales donde es jefe/a de departamento
        $newGroups = $this->findByOrganizationAndHeadPerson($organization, $person);
        $this->appendGroups($groups, $newGroups);

        // grupos donde es tutor
        $newGroups = $this->findByOrganizationAndGroupTutorPerson($organization, $person);
        $this->appendGroups($groups, $newGroups);

        return $groups;
    }

    public function findByAcademicYearAndTeacher(AcademicYear $academicYear, Teacher $teacher)
    {
        // vamos a buscar los grupos a los que tiene acceso
        $groups = new ArrayCollection();

        $newGroups = $this->findByAcademicYearAndWltHead($academicYear, $teacher);
        $this->appendGroups($groups, $newGroups);
        $newGroups = $this->findByAcademicYearAndWltTutor($academicYear, $teacher);
        $this->appendGroups($groups, $newGroups);
        $newGroups = $this->findByAcademicYearAndWltTeacher($academicYear, $teacher);
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
