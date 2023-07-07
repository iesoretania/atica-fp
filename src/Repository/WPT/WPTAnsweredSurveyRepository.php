<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Repository\WPT;

use App\Entity\AnsweredSurvey;
use App\Entity\WPT\EducationalTutorAnsweredSurvey;
use App\Entity\WPT\Shift;
use App\Entity\WPT\WorkTutorAnsweredSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WPTAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnsweredSurvey::class);
    }

    public function findByWorkTutorSurveyShift(
        Shift $shift
    ) {
        $qb = $this->createQueryBuilder('asu')
            ->join(
                WorkTutorAnsweredSurvey::class,
                'wtas',
                'WITH',
                'wtas.shift = :shift AND asu = wtas.answeredSurvey'
            )
            ->setParameter('shift', $shift);

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function findByEducationalTutorSurveyShift(
        Shift $shift
    ) {
        $qb = $this->createQueryBuilder('asu')
            ->join(
                EducationalTutorAnsweredSurvey::class,
                'etas',
                'WITH',
                'etas.shift = :shift AND asu = etas.answeredSurvey'
            )
            ->setParameter('shift', $shift);

        return $qb
            ->getQuery()
            ->getResult();
    }
}
