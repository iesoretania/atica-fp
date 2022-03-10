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

namespace App\Repository\WPT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\WPT\AgreementEnrollment;
use App\Repository\Edu\TeacherRepository;

class WPTTeacherRepository extends TeacherRepository
{
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('t')
            ->join('t.person', 'p')
            ->join(AgreementEnrollment::class, 'ae', 'WITH', 'ae.educationalTutor = t')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function findEducationalTutorsByStudentEnrollment(StudentEnrollment $studentEnrollment)
    {
        return $this->createQueryBuilder('t')
            ->distinct(true)
            ->join(AgreementEnrollment::class, 'ae', 'WITH', 'ae.educationalTutor = t')
            ->join('ae.agreement', 'a')
            ->join('t.person', 'p')
            ->where('ae.studentEnrollment = :student_enrollment')
            ->setParameter('student_enrollment', $studentEnrollment)
            ->getQuery()
            ->getResult();
    }

    public function findByGroups($groups)
    {
        return $this->createQueryBuilder('t')
            ->distinct(true)
            ->join(AgreementEnrollment::class, 'ae', 'WITH', 'ae.educationalTutor = t')
            ->join('ae.studentEnrollment', 'se')
            ->andWhere('se.group IN (:groups)')
            ->setParameter('groups', $groups)
            ->getQuery()
            ->getResult();
    }
}
