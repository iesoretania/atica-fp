<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

namespace AppBundle\Repository\Edu;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\EducationalOrganization;
use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityRepository;

class AcademicYearRepository extends EntityRepository
{
    public function getCurrentByOrganization(Organization $organization)
    {
        return $this->createQueryBuilder('ay')
            ->join(EducationalOrganization::class, 'eo', 'WITH', 'ay = eo.currentAcademicYear')
            ->where('eo.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();
    }

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
}
