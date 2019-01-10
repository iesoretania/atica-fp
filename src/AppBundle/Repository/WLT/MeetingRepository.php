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
use AppBundle\Entity\WLT\Meeting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class MeetingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Meeting::class);
    }

    public function orWhereContainsTextAndInGroups(
        QueryBuilder $queryBuilder,
        AcademicYear $academicYear,
        $text,
        $groups = []
    ) {
        $teacherMeetings = $this->createQueryBuilder('m')
            ->join('m.teachers', 't')
            ->join('t.person', 'p')
            ->orWhere('p.firstName LIKE :tq')
            ->orWhere('p.lastName LIKE :tq')
            ->andWhere('m.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('tq', '%'.$text.'%')
            ->getQuery()
            ->getResult();

        $studentMeetings = $this->createQueryBuilder('m')
            ->join('m.studentEnrollments', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->orWhere('p.firstName LIKE :tq')
            ->orWhere('p.lastName LIKE :tq')
            ->orWhere('g.name LIKE :tq')
            ->andWhere('m.academicYear = :academic_year');

        if ($groups) {
            $studentMeetings = $studentMeetings
                ->andWhere('g IN (:groups)')
                ->setParameter('groups', $groups);
        }

        $studentMeetings = $studentMeetings
            ->setParameter('academic_year', $academicYear)
            ->setParameter('tq', '%'.$text.'%')
            ->getQuery()
            ->getResult();

        return $queryBuilder
            ->orWhere('m IN (:teacher_meetings)')
            ->orWhere('m IN (:student_meetings)')
            ->setParameter('teacher_meetings', $teacherMeetings)
            ->setParameter('student_meetings', $studentMeetings);
    }


    public function orWhereInGroups(
        QueryBuilder $queryBuilder,
        $groups = []
    ) {
        if ($groups) {
            $studentMeetings = $this->createQueryBuilder('m')
                ->join('m.studentEnrollments', 'se')
                ->join('se.group', 'g')
                ->andWhere('g IN (:groups)')
                ->setParameter('groups', $groups)
                ->getQuery()
                ->getResult();

            return $queryBuilder
                ->orWhere('m IN (:student_meetings)')
                ->setParameter('student_meetings', $studentMeetings);
        }
        return $queryBuilder;
    }

    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('m')
            ->where('m IN (:items)')
            ->andWhere('m.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('m.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Meeting::class, 'm')
            ->where('m IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
