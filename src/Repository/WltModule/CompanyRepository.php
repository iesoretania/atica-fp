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

use App\Entity\Company;
use App\Entity\WltModule\LearningProgram;
use App\Entity\WltModule\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CompanyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Company::class);
    }

    public function findByLearningProgramFromProject(Project $project)
    {
        return $this->createQueryBuilder('c')
            ->distinct(true)
            ->join(LearningProgram::class, 'lp', 'WITH', 'c = lp.company')
            ->where('lp.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.name')
            ->getQuery()
            ->getResult();
    }
}
