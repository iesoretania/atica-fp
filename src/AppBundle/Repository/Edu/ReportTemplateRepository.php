<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

use AppBundle\Entity\Edu\ReportTemplate;
use AppBundle\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ReportTemplateRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportTemplate::class);
    }

    public function findByOrganizationQueryBuilder(Organization $organization)
    {
        return $this->createQueryBuilder('tr')
            ->where('tr.organization = :organization')
            ->setParameter('organization', $organization)
            ->orderBy('tr.description');
    }

    public function findByOrganization(Organization $organization)
    {
        return $this->findByOrganizationQueryBuilder($organization)
            ->getQuery()
            ->getResult();
    }

    public function findAllInListByIdAndOrganization(
        $items,
        Organization $organization
    ) {
        return $this->createQueryBuilder('rt')
            ->where('rt.id IN (:items)')
            ->andWhere('rt.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('rt.description')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(ReportTemplate::class, 'rt')
            ->where('rt IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
