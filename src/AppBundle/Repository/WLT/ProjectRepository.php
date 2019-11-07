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

namespace AppBundle\Repository\WLT;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Person;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\Project;
use AppBundle\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function countByOrganizationAndManagerPerson(Organization $organization, Person $manager)
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p)')
            ->where('p.organization = :organization')
            ->andWhere('p.manager = :manager')
            ->setParameter('organization', $organization)
            ->setParameter('manager', $manager)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllInListByIdAndOrganization(
        $items,
        Organization $organization
    ) {
        return $this->createQueryBuilder('p')
            ->where('p IN (:items)')
            ->andWhere('p.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByManager(Person $person)
    {
        return $this->createQueryBuilder('p')
            ->where('p.manager = :person')
            ->setParameter('person', $person)
            ->getQuery()
            ->getResult();
    }

    public function findByTeacher(Teacher $teacher)
    {
        return $this->createQueryBuilder('p')
            ->distinct(true)
            ->join('p.groups', 'g')
            ->join('g.teachings', 'te')
            ->where('te.teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    public function findByIds(
        $items
    ) {
        return $this->createQueryBuilder('p')
            ->where('p IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Project::class, 'p')
            ->where('p IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function findByOrganization(
        Organization $organization
    ) {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYear(
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('p')
            ->distinct(true)
            ->join('p.groups', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYearAndWorkcenter(
        AcademicYear $academicYear,
        Workcenter $workcenter
    ) {
        return $this->createQueryBuilder('p')
            ->distinct(true)
            ->join('p.groups', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join(Agreement::class, 'a', 'WITH', 'a.project = p')
            ->andWhere('t.academicYear = :academic_year')
            ->andWhere('a.workcenter = :workcenter')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('workcenter', $workcenter)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByOrganizationAndManagerPerson(
        Organization $organization,
        Person $person
    ) {
        return $this->createQueryBuilder('p')
            ->andWhere('p.organization = :organization')
            ->andWhere('p.manager = :person')
            ->setParameter('organization', $organization)
            ->setParameter('person', $person)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByGroups(
        $groups
    ) {
        return $this->createQueryBuilder('p')
            ->distinct(true)
            ->join('p.groups', 'g')
            ->andWhere('g IN (:groups)')
            ->setParameter('groups', $groups)
            ->orderBy('p.name', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
