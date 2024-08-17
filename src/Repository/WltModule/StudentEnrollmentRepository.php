<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Repository\WltModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\WltModule\Agreement;
use App\Entity\WltModule\Project;
use App\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StudentEnrollmentRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentEnrollment::class);
    }

    public function findByProjectsQueryBuilder(
        $projects
    ) {
        return $this->createQueryBuilder('se')
            ->join('se.person', 's')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->join('t.academicYear', 'a')
            ->join(Project::class, 'p', 'WITH', 'se MEMBER OF p.studentEnrollments')
            ->andWhere('p IN (:projects)')
            ->setParameter('projects', $projects)
            ->addOrderBy('a.description')
            ->addOrderBy('g.name')
            ->addOrderBy('s.lastName')
            ->addOrderBy('s.firstName');
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     */
    public function findByProjectsAndAgreementDateQueryBuilder(
        $projects,
        \DateTimeInterface $dateTime = null
    ) {
        $qb = $this->findByProjectsQueryBuilder($projects);
        if ($dateTime instanceof \DateTimeInterface) {
            $startDate = clone $dateTime;
            $startDate->setTime(0, 0);
            $endDate = clone $startDate;
            $endDate->add(new \DateInterval('P1D'));

            $qb
                ->join(Agreement::class, 'ag', 'WITH', 'ag.studentEnrollment = se')
                ->andWhere('ag.startDate <= :start_date_time')
                ->andWhere('ag.endDate >= :end_date_time')
                ->setParameter('start_date_time', $startDate)
                ->setParameter('end_date_time', $endDate);
        }

        return $qb;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     */
    public function findByWorkcenterProjectsAndAgreementDate(
        Workcenter $workcenter,
        $projects,
        \DateTimeInterface $dateTime = null
    ) {
        return $this->findByProjectsAndAgreementDateQueryBuilder($projects, $dateTime)
            ->andWhere('ag.workcenter = :workcenter')
            ->setParameter('workcenter', $workcenter)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     */
    public function findByProjectAndAcademicYearDate(Project $project, \DateTimeInterface $dateTime = null)
    {
        $startDate = clone $dateTime;
        $startDate->setTime(0, 0);
        $endDate = clone $dateTime;
        $startDate->add(new \DateInterval('P1D'));
        $qb = $this->findByProjectsQueryBuilder([$project]);

        if ($dateTime instanceof \DateTimeInterface) {
            $qb
                ->andWhere('a.startDate < :end_date_time')
                ->andWhere('a.endDate >= :start_date_time')
                ->setParameter('start_date_time', $startDate)
                ->setParameter('end_date_time', $endDate);
        }
        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findByProjectAndAcademicYear(Project $project, ?AcademicYear $academicYear)
    {
        $qb = $this->findByProjectsQueryBuilder([$project]);

        if ($academicYear instanceof AcademicYear) {
            $qb
                ->andWhere('a = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}
