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

namespace App\Repository\Edu;

use App\Entity\Edu\Grade;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Edu\Training;
use App\Entity\Organization;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EduOrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function findByCurrentStudent(Person $person)
    {
        return $this->createQueryBuilder('o')
            ->distinct()
            ->join('o.currentAcademicYear', 'ay')
            ->join(Training::class, 't', 'WITH', 't.academicYear = ay')
            ->join(Grade::class, 'gr', 'WITH', 'gr.training = t')
            ->join('gr.groups', 'g')
            ->join(StudentEnrollment::class, 'se', 'WITH', 'se.group = g')
            ->where('se.person = :user')
            ->setParameter('user', $person)
            ->getQuery()
            ->getResult();
    }

    public function findByCurrentTeacher(Person $person)
    {
        return $this->createQueryBuilder('o')
            ->join('o.currentAcademicYear', 'ay')
            ->leftJoin(Teacher::class, 'te', 'WITH', 'te.academicYear = ay')
            ->where('te.person = :user')
            ->setParameter('user', $person)
            ->getQuery()
            ->getResult();
    }
}
