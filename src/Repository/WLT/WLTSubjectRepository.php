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

namespace App\Repository\WLT;

use App\Entity\Edu\Subject;
use App\Entity\WLT\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class WLTSubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subject::class);
    }

    /**
     * @param string $code
     * @return Subject|null
     */
    public function findOneByProjectAndCode(Project $project, $code)
    {
        try {
            return $this->createQueryBuilder('s')
                ->distinct(true)
                ->join('s.grade', 'g')
                ->join('g.groups', 'gr')
                ->andWhere('s.code = :code')
                ->andWhere('gr IN (:groups)')
                ->setParameter('code', $code)
                ->setParameter('groups', $project->getGroups())
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }
}
