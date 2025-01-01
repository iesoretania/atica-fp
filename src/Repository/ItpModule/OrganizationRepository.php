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

use App\Entity\Edu\Grade;
use App\Entity\Edu\Training;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\Organization;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, Organization::class);
    }

    public function findByWorkTutor(Person $person): array
    {
        return $this->createQueryBuilder('o')
            ->distinct()
            ->join(Training::class, 't', 'WITH', 't.academicYear = o.currentAcademicYear')
            ->join(Grade::class, 'gr', 'WITH', 'gr.training = t')
            ->join(ProgramGrade::class, 'pg', 'WITH', 'pg.grade = gr')
            ->join(ProgramGroup::class, 'pgg', 'WITH', 'pgg.programGrade = pg')
            ->join('pgg.studentPrograms', 'sp')
            ->join(StudentProgramWorkcenter::class, 'spw', 'WITH', 'spw.studentProgram = sp')
            ->where('spw.workTutor = :user OR spw.additionalWorkTutor = :user')
            ->setParameter('user', $person)
            ->getQuery()
            ->getResult();
    }
}
