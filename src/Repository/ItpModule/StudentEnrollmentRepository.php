<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Repository\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class StudentEnrollmentRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentEnrollment::class);
    }

    public function findByTrainingProgramQueryBuilder(array $trainingPrograms): QueryBuilder
    {
        return $this->createQueryBuilder('se')
            ->join(StudentProgram::class, 'sp', 'WITH', 'se = sp.studentEnrollment')
            ->join('sp.studentProgramWorkcenters', 'spw')
            ->join('se.person', 's')
            ->join('sp.programGroup', 'pg')
            ->join('pg.group', 'g')
            ->join('pg.programGrade', 'pgg')
            ->join('pgg.trainingProgram', 'tp')
            ->andWhere('tp IN (:training_programs)')
            ->setParameter('training_programs', $trainingPrograms)
            ->addOrderBy('tp.name')
            ->addOrderBy('g.name')
            ->addOrderBy('s.lastName')
            ->addOrderBy('s.firstName');
    }

    /**
     * @param \DateTime|\DateTimeImmutable $dateTime
     */
    public function findByTrainingProgramAndAgreementDateQueryBuilder(
        $trainingPrograms,
        \DateTimeInterface $dateTime = null
    )
    {
        $qb = $this->findByTrainingProgramQueryBuilder($trainingPrograms);
        if ($dateTime instanceof \DateTimeInterface) {
            $startDate = clone $dateTime;
            $startDate->setTime(0, 0);
            $endDate = clone $startDate;
            $endDate->add(new \DateInterval('P1D'));

            $qb
                ->andWhere('spw.startDate <= :start_date_time')
                ->andWhere('spw.endDate >= :end_date_time')
                ->setParameter('start_date_time', $startDate)
                ->setParameter('end_date_time', $endDate);
        }

        return $qb;
    }

    public function findByWorkcenterTrainingProgramsAndAgreementDate(
        Workcenter         $workcenter,
        array              $trainingPrograms,
        \DateTimeInterface $dateTime = null
    ): array
    {
        return $this->findByTrainingProgramAndAgreementDateQueryBuilder($trainingPrograms, $dateTime)
            ->andWhere('spw.workcenter = :workcenter')
            ->setParameter('workcenter', $workcenter)
            ->getQuery()
            ->getResult();
    }

    public function findByTrainingProgramAndAcademicYearDate(
        TrainingProgram    $trainingProgram,
        \DateTimeInterface $dateTime = null
    ): array
    {
        $qb = $this->findByTrainingProgramQueryBuilder([$trainingProgram]);

        if ($dateTime instanceof \DateTimeInterface) {
            $startDate = \DateTime::createFromInterface($dateTime);
            $startDate->setTime(0, 0);
            $endDate = clone $startDate;
            $startDate->add(new \DateInterval('P1D'));
            $qb
                ->andWhere('spw.startDate < :end_date_time')
                ->andWhere('spw.endDate >= :start_date_time')
                ->setParameter('start_date_time', $startDate)
                ->setParameter('end_date_time', $endDate);
        }
        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findByTrainingProgramAndAcademicYear(TrainingProgram $trainingProgram, ?AcademicYear $academicYear)
    {
        $qb = $this->findByTrainingProgramQueryBuilder([$trainingProgram]);

        if ($academicYear instanceof AcademicYear) {
            $qb
                ->join('pgg.grade', 'g')
                ->join('g.training', 't')
                ->andWhere('t = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}
