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
use AppBundle\Entity\WLT\ManagerAnsweredSurvey;
use AppBundle\Entity\WLT\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ManagerAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ManagerAnsweredSurvey::class);
    }

    public function findOneByProjectAndAcademicYear(Project $project, AcademicYear $academicYear)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('mas')
            ->from(ManagerAnsweredSurvey::class, 'mas')
            ->where('mas.project = :project')
            ->andWhere('mas.academicYear = :academic_year')
            ->setParameter('project', $project)
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
