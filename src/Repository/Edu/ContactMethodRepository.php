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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\ContactMethod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Persistence\ManagerRegistry;

class ContactMethodRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry
    ) {
        parent::__construct($registry, ContactMethod::class);
    }

    private function findByAcademicYearAndState(AcademicYear $academicYear, bool $enabled)
    {
        return $this->createQueryBuilder('cm')
            ->andWhere('cm.academicYear = :academic_year')
            ->andWhere('cm.enabled = :enabled')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('enabled', $enabled)
            ->orderBy('cm.description')
            ->getQuery()
            ->getResult();
    }

    public function findEnabledByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearAndState($academicYear, true);
    }

    public function findDisabledByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearAndState($academicYear, false);
    }

    /**
     * @param $items
     * @param AcademicYear $academicYear
     * @return ContactMethod[]|Collection
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('cm')
            ->where('cm.id IN (:items)')
            ->andWhere('cm.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('cm.description')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(ContactMethod::class, 'cm')
            ->where('cm IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function getFilteredAndByAcademicYear(AcademicYear $academicYear, ?string $q = '') {

        $queryBuilder = $this->createQueryBuilder('cm');

        $queryBuilder
            ->orderBy('cm.description');

        if ($q) {
            $queryBuilder
                ->where('cm.description LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        return $queryBuilder
            ->andWhere('cm.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);
    }
}
