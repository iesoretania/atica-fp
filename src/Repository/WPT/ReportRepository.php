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

use App\Entity\WPT\AgreementEnrollment;
use App\Entity\WPT\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function deleteFromAgreements($list)
    {
        $ids = $this->getEntityManager()->createQueryBuilder()
            ->select('ae.id')
            ->from(AgreementEnrollment::class, 'ae')
            ->where('ae.agreement IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->getResult();

        return $this->deleteFromAgreementEnrollments($ids);
    }

    public function deleteFromAgreementEnrollments($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Report::class, 'r')
            ->where('r.agreementEnrollment IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
