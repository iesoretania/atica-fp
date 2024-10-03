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

use App\Entity\Edu\LearningOutcome;
use App\Entity\ItpModule\ProgramGrade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class LearningOutcomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LearningOutcome::class);
    }

    final public function createLearningOutcomeByProgramGradeQueryBuilder(ProgramGrade $programGrade, mixed $q): QueryBuilder
    {
        return $this->createQueryBuilder('lo')
            ->join('lo.subject', 's')
            ->join('s.grade', 'g')
            ->join(ProgramGrade::class, 'pg', 'WITH', 'pg.grade = g')
            ->andWhere('pg = :programGrade')
            ->addOrderBy('s.code', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->addOrderBy('lo.code', 'ASC')
            ->andWhere('lo.code LIKE :tq OR lo.description LIKE :tq OR s.code LIKE :tq OR s.name LIKE :tq')
            ->setParameter('programGrade', $programGrade)
            ->setParameter('tq', '%' . $q . '%');
    }
}
