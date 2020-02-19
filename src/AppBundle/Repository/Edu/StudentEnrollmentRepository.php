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

namespace AppBundle\Repository\Edu;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\StudentEnrollment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class StudentEnrollmentRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentEnrollment::class);
    }

    public function findByGroups($groups)
    {
        return $this->createQueryBuilder('se')
            ->join('se.person', 's')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('t.academicYear', 'a')
            ->where('se.group IN (:groups)')
            ->addOrderBy('a.description')
            ->addOrderBy('g.name')
            ->addOrderBy('s.lastName')
            ->addOrderBy('s.firstName')
            ->setParameter('groups', $groups)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @param AcademicYear $academicYear
     * @return StudentEnrollment[]
     */
    public function findInListByAcademicYear($items, AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('t.academicYear = :academic_year')
            ->andWhere('se IN (:items)')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('items', $items)
            ->orderBy('p.lastName', 'ASC')
            ->addOrderBy('p.firstName', 'ASC')
            ->addOrderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param StudentEnrollment[] $list
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(StudentEnrollment::class, 'se')
            ->where('se IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
