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

namespace App\Repository\Edu;

use App\Entity\Edu\AcademicYear;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AcademicYearRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AcademicYear::class);
    }

    /**
     * @return AcademicYear[]
     */
    public function findAllByOrganization(Organization $organization)
    {
        return $this->createQueryBuilder('ay')
            ->andWhere('ay.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('ay.description', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @return AcademicYear[]
     */
    public function findAllInListByIdAndOrganizationButCurrent(
        $items,
        Organization $organization,
        AcademicYear $current
    ) {
        return $this->createQueryBuilder('ay')
            ->where('ay.id IN (:items)')
            ->andWhere('ay != :current')
            ->andWhere('ay.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('current', $current)
            ->setParameter('organization', $organization)
            ->orderBy('ay.description')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param \DateTime|\DateTimeImmutable $date
     */
    public function findByDate(\DateTimeInterface $date)
    {
        $startDate = clone $date;
        $startDate->setTime(0, 0);

        $endDate = clone $date;
        $endDate->add(new \DateInterval('P1D'));

        return $this->createQueryBuilder('ay')
            ->where('ay.startDate <= :start_date')
            ->andWhere('ay.endDate > :end_date')
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate)
            ->orderBy('ay.organization')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AcademicYear[]
     */
    public function findAllByOrganizationButOne(Organization $organization, AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('ay')
            ->andWhere('ay.organization = :organization')
            ->andWhere('ay != :academic_year')
            ->setParameter('organization', $organization)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('ay.description', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
