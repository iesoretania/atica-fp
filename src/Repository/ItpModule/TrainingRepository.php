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

namespace App\Repository\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\TrainingProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    /**
     * @return QueryBuilder
     */
    private function findByAcademicYearQueryBuilder(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('t')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('t.name');
    }

    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('t')
            ->select('t')
            ->join(TrainingProgram::class, 'tp', 'WITH', 'tp.training = t')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getResult();
    }

    public function findNotRegisteredByAcademicYear(AcademicYear $academicYear)
    {
        $selected = $this->createQueryBuilder('t')
            ->select('t')
            ->join(TrainingProgram::class, 'tp', 'WITH', 'tp.training = t')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getResult();

        $qb = $this->findByAcademicYearQueryBuilder($academicYear);

        if (count($selected) !== 0) {
            $qb
                ->andWhere('t NOT IN (:selected)')
                ->setParameter('selected', $selected);
        }

        return $qb->getQuery()
            ->getResult();
    }
}
