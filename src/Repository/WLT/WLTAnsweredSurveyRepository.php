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

namespace App\Repository\WLT;

use App\Entity\AnsweredSurvey;
use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\WLT\EducationalTutorAnsweredSurvey;
use App\Entity\WLT\ManagerAnsweredSurvey;
use App\Entity\WLT\Project;
use App\Entity\WLT\WorkTutorAnsweredSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WLTAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnsweredSurvey::class);
    }

    public function findByWorkTutorSurveyProjectAndAcademicYear(
        Project $project,
        ?AcademicYear $academicYear = null
    ) {
        $qb = $this->createQueryBuilder('asu')
            ->join(
                WorkTutorAnsweredSurvey::class,
                'wtas',
                'WITH',
                'wtas.project = :project AND asu = wtas.answeredSurvey'
            )
            ->setParameter('project', $project);

        if ($academicYear) {
            $qb
                ->where('wtas.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findByEducationalTutorSurveyProjectAndAcademicYear(
        Project $project,
        ?AcademicYear $academicYear = null
    ) {
        $qb = $this->createQueryBuilder('asu')
            ->join(
                EducationalTutorAnsweredSurvey::class,
                'etas',
                'WITH',
                'etas.project = :project AND asu = etas.answeredSurvey'
            )
            ->setParameter('project', $project);

        if ($academicYear) {
            $qb
                ->join(
                    Teacher::class,
                    'te',
                    'WITH',
                    'etas.teacher = te AND te.academicYear = :academic_year'
                )
                ->setParameter('academic_year', $academicYear);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}
