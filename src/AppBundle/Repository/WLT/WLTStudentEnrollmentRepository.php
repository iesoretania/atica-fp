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

namespace AppBundle\Repository\WLT;

use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\Project;
use AppBundle\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class WLTStudentEnrollmentRepository extends ServiceEntityRepository
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

    public function findByProjectsAndAgreementDateQueryBuilder(
        $projects,
        \DateTime $dateTime = null
    ) {
        $qb = $this->findByProjectsQueryBuilder($projects);
        if ($dateTime) {
            $startDate = clone $dateTime;
            $startDate->setTime(0, 0, 0);
            $endDate = clone $dateTime;
            $startDate->add(new \DateInterval('P1D'));

            $qb
                ->join(Agreement::class, 'ag', 'WITH', 'ag.studentEnrollment = se')
                ->andWhere('ag.startDate <= :start_date_time')
                ->andWhere('ag.endDate >= :end_date_time')
                ->setParameter('start_date_time', $startDate)
                ->setParameter('end_date_time', $endDate);
        }

        return $qb;
    }

    public function findByWorkcenterProjectsAndAgreementDate(
        Workcenter $workcenter,
        $projects,
        \DateTime $dateTime = null
    ) {
        return $this->findByProjectsAndAgreementDateQueryBuilder($projects, $dateTime)
            ->andWhere('ag.workcenter = :workcenter')
            ->setParameter('workcenter', $workcenter)
            ->getQuery()
            ->getResult();
    }

    public function findByProjectAndAcademicYearDate(Project $project, \DateTime $dateTime = null)
    {
        $startDate = clone $dateTime;
        $startDate->setTime(0, 0, 0);
        $endDate = clone $dateTime;
        $startDate->add(new \DateInterval('P1D'));
        $qb = $this->findByProjectsQueryBuilder([$project]);

        if ($dateTime) {
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
}
