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

use App\Entity\Edu\Teacher;
use App\Entity\WPT\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findAllInListById($items)
    {
        return $this->createQueryBuilder('v')
            ->where('v IN (:items)')
            ->join('v.workcenter', 'w')
            ->setParameter('items', $items)
            ->orderBy('v.dateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Contact[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Contact::class, 'v')
            ->where('v IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function findByTeacherOrderByDateTime(Teacher $teacher)
    {
        return $this->createQueryBuilder('v')
            ->where('v.teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('v.dateTime', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
