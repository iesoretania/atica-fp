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

use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\WPT\Agreement;
use AppBundle\Repository\WorkcenterRepository;

class WPTWorkcenterRepository extends WorkcenterRepository
{
    public function findByWPTEducationalTutor(
        Teacher $teacher
    ) {
        return $this->createQueryBuilder('w')
            ->join(Agreement::class, 'a', 'WITH', 'a.workcenter = w')
            ->join('a.agreementEnrollments', 'ae')
            ->join('w.company', 'c')
            ->andWhere('ae.educationalTutor = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('c.name')
            ->addOrderBy('w.name')
            ->getQuery()
            ->getResult();
    }
}
