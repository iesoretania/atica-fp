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

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGradeLearningOutcome;
use App\Repository\Edu\CriterionRepository as EduCriterionRepository;
use Doctrine\Persistence\ManagerRegistry;

class CriterionRepository extends EduCriterionRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    public function findByProgramGrade(ProgramGrade $programGrade): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.learningOutcome', 'lo')
            ->join('lo.subject', 's')
            ->join(ProgramGradeLearningOutcome::class, 'pglo', 'WITH', 'pglo.learningOutcome = lo')
            ->where('pglo.programGrade = :program_grade')
            ->setParameter('program_grade',$programGrade)
            ->addOrderBy('s.name')
            ->addOrderBy('lo.code')
            ->addOrderBy('lo.description')
            ->addOrderBy('c.code')
            ->addOrderBy('c.description')
            ->getQuery()
            ->getResult();
    }
}
