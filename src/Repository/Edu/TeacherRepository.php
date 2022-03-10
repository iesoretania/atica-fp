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

namespace App\Repository\Edu;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Edu\Teaching;
use App\Entity\Organization;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class TeacherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Teacher::class);
    }

    public function findByOrganization(Organization $organization)
    {
        return $this->createQueryBuilder('t')
            ->addSelect('p')
            ->join('t.person', 'p')
            ->join('t.academicYear', 'a')
            ->andWhere('a.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('t')
            ->addSelect('p')
            ->join('t.person', 'p')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function findOneByAcademicYearAndPerson(AcademicYear $academicYear, Person $person)
    {
        return $this->createQueryBuilder('t')
            ->join('t.person', 'p')
            ->where('p = :person')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('person', $person)
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByAcademicYearAndInternalCode(AcademicYear $academicYear, $internalCode)
    {
        try {
            return $this->createQueryBuilder('t')
                ->join('t.person', 'p')
                ->andWhere('t.academicYear = :academic_year')
                ->andWhere('p.internalCode = :internal_code')
                ->setParameter('academic_year', $academicYear)
                ->setParameter('internal_code', $internalCode)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function findByGroups($groups)
    {
        return $this->createQueryBuilder('t')
            ->join(Teaching::class, 'te', 'WITH', 'te.teacher = t')
            ->andWhere('te.group IN (:groups)')
            ->setParameter('groups', $groups)
            ->getQuery()
            ->getResult();
    }

    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('t')
            ->join('t.person', 'p')
            ->where('t.id IN (:items)')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function findOneByPersonAndAcademicYear(
        Person $person,
        AcademicYear $academicYear
    ) {
        try {
            return $this->createQueryBuilder('t')
                ->where('t.person = :person')
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('person', $person)
                ->setParameter('academic_year', $academicYear)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }
}
