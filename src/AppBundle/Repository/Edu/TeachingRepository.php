<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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
use AppBundle\Entity\Edu\Teaching;
use AppBundle\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class TeachingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Teaching::class);
    }

    /**
     * @param Teaching[]
     * @return mixed
     */
    public function deleteFromSubjectList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Teaching::class, 't')
            ->where('t.subject IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function findByAcademicYearAndPersonQueryBuilder(AcademicYear $academicYear, Person $person)
    {
        return $this->createQueryBuilder('t')
            ->join('t.subject', 's')
            ->join('s.grade', 'g')
            ->join('g.training', 'tr')
            ->join('t.teacher', 'te')
            ->where('te.person = :person')
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('person', $person)
            ->setParameter('academic_year', $academicYear);
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $person
     *
     * @return int
     */
    public function countAcademicYearAndWltPerson(AcademicYear $academicYear, Person $person)
    {
        try {
            return $this->findByAcademicYearAndPersonQueryBuilder($academicYear, $person)
                ->select('COUNT(t)')
                ->andWhere('t.workLinked = :work_linked')
                ->setParameter('work_linked', true)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        } catch (NoResultException $e) {
            return 0;
        }
    }

    /**
     * @param Group $group
     * @param Person $person
     *
     * @return int
     */
    public function countByGroupAndPerson(Group $group, Person $person)
    {
        try {
            return  $this->createQueryBuilder('t')
                ->join('t.teacher', 'te')
                ->join('t.subject', 's')
                ->join('s.grade', 'g')
                ->select('COUNT(t)')
                ->andWhere('te.person = :teacher_person')
                ->andWhere('s.grade = :grade')
                ->setParameter('teacher_person', $person)
                ->setParameter('grade', $group->getGrade())
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NonUniqueResultException $e) {
            return 0;
        } catch (NoResultException $e) {
            return 0;
        }
    }
}
