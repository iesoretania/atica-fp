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

namespace App\Repository\WltModule;

use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\WltModule\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationRepository extends ServiceEntityRepository
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
            ->join(Project::class, 'p', 'WITH', 'p.organization = o')
            ->join('p.agreements', 'a')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't', 'WITH', 't.academicYear = o.currentAcademicYear')
            ->where('a.workTutor = :user OR a.additionalWorkTutor = :user')
            ->setParameter('user', $person)
            ->getQuery()
            ->getResult();
    }
}
