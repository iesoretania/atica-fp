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

use App\Entity\Edu\Grade;
use App\Entity\Edu\Training;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\WPT\Shift;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WPTOrganizationRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, Organization::class);
    }

    public function findByWorkTutor(Person $person)
    {
        return $this->createQueryBuilder('o')
            ->distinct()
            ->join(Training::class, 't', 'WITH', 't.academicYear = o.currentAcademicYear')
            ->join(Grade::class, 'gr', 'WITH', 'gr.training = t')
            ->join('gr.subjects', 'su')
            ->join(Shift::class, 's', 'WITH', 's.subject = su')
            ->join('s.agreements', 'a')
            ->join('a.agreementEnrollments', 'ae')
            ->where('ae.workTutor = :user OR ae.additionalWorkTutor = :user')
            ->setParameter('user', $person)
            ->getQuery()
            ->getResult();
    }
}
