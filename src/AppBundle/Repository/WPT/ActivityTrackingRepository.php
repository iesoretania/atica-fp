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

use AppBundle\Entity\WPT\ActivityTracking;
use AppBundle\Entity\WPT\AgreementEnrollment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ActivityTrackingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityTracking::class);
    }

    public function getTotalHoursFromAgreementEnrollment(AgreementEnrollment $agreementEnrollment)
    {
        return $this->createQueryBuilder('at')
            ->select('SUM(at.hours)')
            ->join('at.trackedWorkDay', 'twd')
            ->where('twd.agreementEnrollment = :agreement_enrollment')
            ->setParameter('agreement_enrollment', $agreementEnrollment)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCountFromAgreementEnrollment(AgreementEnrollment $agreementEnrollment)
    {
        return $agreementEnrollment->getActivities()->count();
    }

    public function getTrackedCountFromAgreementEnrollment(AgreementEnrollment $agreementEnrollment)
    {
        return $this->createQueryBuilder('at')
            ->select('COUNT(DISTINCT at.activity)')
            ->join('at.trackedWorkDay', 'twd')
            ->where('twd.agreementEnrollment = :agreement_enrollment')
            ->setParameter('agreement_enrollment', $agreementEnrollment)
            ->getQuery()
            ->getSingleScalarResult();
    }

}
