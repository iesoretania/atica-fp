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

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WLT\EducationalTutorAnsweredSurvey;
use AppBundle\Entity\WLT\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class EducationalTutorAnsweredSurveryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EducationalTutorAnsweredSurvey::class);
    }

    public function findOneByProjectAcademicYearAndTeacher(
        Project $project,
        AcademicYear $academicYear,
        Teacher $teacher
    ) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('etas')
            ->from(EducationalTutorAnsweredSurvey::class, 'etas')
            ->where('etas.project = :project')
            ->andWhere('etas.teacher = :teacher')
            ->andWhere('etas.academicYear = :academicYear')
            ->setParameter('project', $project)
            ->setParameter('teacher', $teacher)
            ->setParameter('academicYear', $academicYear)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
