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

namespace App\Repository\WPT;

use App\Entity\WPT\AgreementEnrollment;
use App\Entity\WPT\TrackedWorkDay;
use App\Entity\WPT\WorkDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class TrackedWorkDayRepository extends ServiceEntityRepository
{
    private $workDayRepository;

    public function __construct(
        ManagerRegistry $registry,
        WorkDayRepository $workDayRepository
    ) {
        parent::__construct($registry, TrackedWorkDay::class);
        $this->workDayRepository = $workDayRepository;
    }

    /**
     * @param $list
     * @param AgreementEnrollment $agreementEnrollment
     * @return TrackedWorkDay[]
     */
    public function findInListByWorkDayIdAndAgreementEnrollment($list, AgreementEnrollment $agreementEnrollment)
    {
        $workDays = $this->workDayRepository->findInListByIdAndAgreement($list, $agreementEnrollment->getAgreement());

        $result = [];
        /** @var WorkDay $workDay */
        foreach ($workDays as $workDay) {
            $result[] = $this->findOneOrNewByWorkDayAndAgreementEnrollment($workDay, $agreementEnrollment);
        }

        $this->getEntityManager()->flush();

        return $result;
    }

    public function findAndCountByAgreementEnrollment(AgreementEnrollment $agreementEnrollment)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('wd')
            ->addSelect('twd')
            ->addSelect('SUM(act.hours)')
            ->from(WorkDay::class, 'wd')
            ->leftJoin(
                TrackedWorkDay::class,
                'twd',
                'WITH',
                'twd.workDay = wd AND twd.agreementEnrollment = :agreement_enrollment'
            )
            ->leftJoin('twd.trackedActivities', 'act')
            ->where('wd.agreement = :agreement')
            ->setParameter('agreement', $agreementEnrollment->getAgreement())
            ->setParameter('agreement_enrollment', $agreementEnrollment)
            ->addOrderBy('wd.date')
            ->groupBy('wd')
            ->addGroupBy('twd')
            ->getQuery()
            ->getResult();
    }

    public function findByAgreementEnrollmentWithWorkDay(AgreementEnrollment $agreementEnrollment)
    {
        $data = $this->findAndCountByAgreementEnrollment($agreementEnrollment);
        $return = [];
        foreach ($data as $datum) {
            foreach ($datum as $element) {
                $return[] = $element;
            }
        }
        $return = array_chunk($return, 3);
        return $return;
    }

    public function findByAgreementEnrollmentGroupByMonthAndWeekNumber(AgreementEnrollment $agreementEnrollment)
    {
        return self::groupByMonthAndWeekNumber($this->findByAgreementEnrollmentWithWorkDay($agreementEnrollment));
    }

    /**
     * @param TrackedWorkDay[]
     * @param bool
     * @return mixed
     */
    public function updateAttendance($list, $value)
    {
        /** @var TrackedWorkDay $trackedWorkDay */
        foreach ($list as $trackedWorkDay) {
            $trackedWorkDay->getTrackedActivities()->clear();
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->update(TrackedWorkDay::class, 'w')
            ->set('w.absence', ':value')
            ->where('w IN (:list)')
            ->andWhere('w.locked = :locked')
            ->setParameter('list', $list)
            ->setParameter('value', $value)
            ->setParameter('locked', false)
            ->getQuery()
            ->execute();
    }

    public function updateLock($list, AgreementEnrollment $agreementEnrollment, $value)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->update(TrackedWorkDay::class, 'w')
            ->set('w.locked', ':value')
            ->where('w IN (:list)')
            ->andWhere('w.agreementEnrollment = :agreement_enrollment')
            ->setParameter('list', $list)
            ->setParameter('value', $value)
            ->setParameter('agreement_enrollment', $agreementEnrollment)
            ->getQuery()
            ->execute();
    }

    public function updateWeekLock($year, $week, $agreementEnrollment, $value)
    {
        $items = $this->findByYearWeekAndAgreementEnrollment($year, $week, $agreementEnrollment);

        return $this->updateLock($items, $agreementEnrollment, $value);
    }

    public function findByYearWeekAndAgreementEnrollment($year, $week, AgreementEnrollment $agreementEnrollment)
    {
        $workDays = $this->workDayRepository->findByYearWeekAndAgreement(
            $year,
            $week,
            $agreementEnrollment->getAgreement()
        );

        $result = [];
        /** @var WorkDay $workDay */
        foreach ($workDays as $workDay) {
            $result[] = $this->findOneOrNewByWorkDayAndAgreementEnrollment($workDay, $agreementEnrollment);
        }
        $this->getEntityManager()->flush();

        return $result;
    }

    public function getWeekInformation(TrackedWorkDay $firstWorkday)
    {
        $total = 0;
        $current = 0;

        $oldNumWeek = NAN;

        $workDays = $firstWorkday->getWorkDay()->getAgreement()->getWorkdays();

        /** @var Workday $day */
        foreach ($workDays as $day) {
            $numWeek = $day->getDate()->format('W');

            if ($numWeek != $oldNumWeek) {
                $total++;
                $oldNumWeek = $numWeek;
            }

            if ($firstWorkday->getWorkDay()->getDate() == $day->getDate()) {
                $current = $total;
            }
        }

        return ['total' => $total, 'current' => $current];
    }

    public function hoursStatsByAgreementEnrollment(AgreementEnrollment $agreementEnrollment)
    {
        try {
            return $this->getEntityManager()->createQueryBuilder()
                ->select('SUM(wd.hours)')
                ->from(WorkDay::class, 'wd')
                ->addSelect('SUM(CASE WHEN twd.absence = 0 THEN twd.locked * wd.hours ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN twd.absence = 1 THEN wd.hours ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN twd.absence = 2 THEN wd.hours ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN twd.locked = 1 THEN wd.hours ELSE 0 END)')
                ->addSelect('COUNT(wd)')
                ->addSelect('SUM(CASE WHEN twd.absence = 0 THEN twd.locked ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN twd.absence = 1 THEN 1 ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN twd.absence = 2 THEN 1 ELSE 0 END)')
                ->addSelect('SUM(twd.locked)')
                ->join('wd.agreement', 'a')
                ->leftJoin(
                    TrackedWorkDay::class,
                    'twd',
                    'WITH',
                    'twd.workDay = wd AND twd.agreementEnrollment = :agreement_enrollment'
                )
                ->where('wd.agreement = :agreement')
                ->setParameter('agreement', $agreementEnrollment->getAgreement())
                ->setParameter('agreement_enrollment', $agreementEnrollment)
                ->groupBy('a')
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    public static function groupByMonthAndWeekNumber($workDays)
    {
        $collection = [];

        $oneDayMore = new \DateInterval('P1D');

        foreach ($workDays as $workDayData) {
            /** @var WorkDay $workDay */
            $workDay = $workDayData[0];
            $date = $workDay->getDate();
            $month = (int) $date->format('n');
            $monthCode = (int) $date->format('Y') * 12 + $month - 1;

            if (false === isset($collection[$monthCode])) {
                $firstMonthDate = date_create($date->format('Y-m-01'));
                $firstMonthDow = (int) $firstMonthDate->format('N') - 1;
                $currentDate = clone $firstMonthDate;
                $currentDate->sub(new \DateInterval('P' . $firstMonthDow . 'D'));
                $dateLast = date_create($date->format('Y-m-t'));
                while ($currentDate <= $dateLast) {
                    $currentWeek = (int) $currentDate->format('W');
                    $currentDay = (int) $currentDate->format('d');
                    $currentMonth = (int) $currentDate->format('n');
                    $sign = ($currentMonth !== $month) ? -1 : 1;
                    $collection[$monthCode][$currentWeek]['days'][$sign * $currentDay] = [];
                    $currentDate->add($oneDayMore);
                }
            }

            $currentWeek = (int) $date->format('W');
            $currentDay = (int) $date->format('d');
            $collection[$monthCode][$currentWeek]['days'][$currentDay] = $workDayData;
        }

        return $collection;
    }

    /**
     * @param WorkDay $workDay
     * @param AgreementEnrollment $agreementEnrollment
     * @return TrackedWorkDay|null
     * @throws NonUniqueResultException
     */
    public function findOneOrNewByWorkDayAndAgreementEnrollment(
        WorkDay $workDay,
        AgreementEnrollment $agreementEnrollment
    ) {
        $trackedWorkDay = $this->createQueryBuilder('twd')
            ->where('twd.agreementEnrollment = :agreement_enrollment')
            ->andWhere('twd.workDay = :work_day')
            ->setParameter('agreement_enrollment', $agreementEnrollment)
            ->setParameter('work_day', $workDay)
            ->getQuery()
            ->getOneOrNullResult();

        if (null === $trackedWorkDay && $agreementEnrollment->getAgreement() === $workDay->getAgreement()) {
            $trackedWorkDay = new TrackedWorkDay();
            $trackedWorkDay
                ->setAgreementEnrollment($agreementEnrollment)
                ->setWorkDay($workDay);
            $this->getEntityManager()->persist($trackedWorkDay);
        }

        return $trackedWorkDay;
    }
}
