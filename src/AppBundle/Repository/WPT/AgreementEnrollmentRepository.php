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

namespace AppBundle\Repository\WPT;

use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WPT\AgreementEnrollment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class AgreementEnrollmentRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, AgreementEnrollment::class);
    }

    public function findByStudentEnrollment(StudentEnrollment $studentEnrollment)
    {
        return $this->createQueryBuilder('ae')
            ->join('ae.agreement', 'a')
            ->where('ae.studentEnrollment = :student_enrollment')
            ->setParameter('student_enrollment', $studentEnrollment)
            ->orderBy('a.startDate')
            ->addOrderBy('a.endDate')
            ->addOrderBy('a.signDate')
            ->getQuery()
            ->getResult();
    }

    public function findByEducationalTutor(Teacher $teacher)
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.educationalTutor = :teacher')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

}
