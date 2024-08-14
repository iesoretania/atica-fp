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

use App\Entity\WPT\Agreement;
use App\Entity\WPT\TrackedWorkDay;
use App\Entity\WPT\WorkDay;
use App\Repository\Edu\NonWorkingDayRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

class WorkDayRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly NonWorkingDayRepository $nonWorkingDayRepository,
        private readonly ActivityTrackingRepository $activityTrackingRepository
    ) {
        parent::__construct($registry, WorkDay::class);
    }

    public function findByAgreement(Agreement $agreement)
    {
        return $this->createQueryBuilder('wr')
            ->where('wr.agreement = :agreement')
            ->setParameter('agreement', $agreement)
            ->addOrderBy('wr.date')
            ->getQuery()
            ->getResult();
    }

    public function getTotalHoursByAgreement(Agreement $agreement)
    {
        try {
            return $this->createQueryBuilder('wr')
                ->select('SUM(wr.hours)')
                ->where('wr.agreement = :agreement')
                ->setParameter('agreement', $agreement)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }
        return 0;
    }

    /**
     * @param $list
     * @return WorkDay[]
     */
    public function findInListByIdAndAgreement($list, Agreement $agreement)
    {
        return $this->createQueryBuilder('wr')
            ->where('wr.agreement = :agreement')
            ->andWhere('wr IN (:list)')
            ->setParameter('agreement', $agreement)
            ->setParameter('list', $list)
            ->addOrderBy('wr.date')
            ->getQuery()
            ->getResult();
    }

    public function findOneByAgreementAndDate(Agreement $agreement, \DateTimeInterface $date)
    {
        return $this->createQueryBuilder('wr')
            ->where('wr.agreement = :agreement')
            ->andWhere('wr.date = :date')
            ->setParameter('agreement', $agreement)
            ->setParameter('date', $date)
            ->addOrderBy('wr.date')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countByAgreement(Agreement $agreement)
    {
        try {
            return $this->createQueryBuilder('wr')
                ->select('COUNT(wr)')
                ->where('wr.agreement = :agreement')
                ->setParameter('agreement', $agreement)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }
        return 0;
    }

    public function getAgreementTrackedHours(Agreement $agreement)
    {
        try {
            return $this->createQueryBuilder('wr')
                ->select('SUM(wr.hours)')
                ->where('wr.agreement = :agreement')
                ->setParameter('agreement', $agreement)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }
        return 0;
    }

    public function findByAgreementGroupByMonthAndWeekNumber(Agreement $agreement): array
    {
        return self::groupByMonthAndWeekNumber($this->findByAgreement($agreement));
    }

    public function createWorkDayCollectionByAcademicYear(
        Agreement $agreement,
        \DateTimeInterface $startDate,
        $hours,
        $weekHours,
        $overwrite = false,
        $ignoreNonWorkingDays = false
    ) {
        $academicYear = $agreement->getShift()->getGrade()->getTraining()->getAcademicYear();

        $countCurrentWorkDays = $this->countByAgreement($agreement);

        $collection = new ArrayCollection();
        $count = 0;
        foreach ($weekHours as $hourCount) {
            $count += $hourCount;
        }

        if ($count <= 0) {
            return $collection;
        }

        $date = new \DateTime(
            $startDate->format('Y-m-d') . ' 00:00:00',
            new \DateTimeZone('UTC')
        );

        $oneMoreDay = new \DateInterval('P1D');
        $nonWorkingDaysData = [];

        if (!$ignoreNonWorkingDays) {
            $nonWorkingDays = $this->nonWorkingDayRepository->findByAcademicYear($academicYear);

            foreach ($nonWorkingDays as $nonWorkingDay) {
                $nonWorkingDaysData[$nonWorkingDay->getDate()->format('Ymd')] = 1;
            }
        }

        while ($hours > 0) {
            $dow = (int) $date->format('N') - 1;
            if ($weekHours[$dow] > 0 && !isset($nonWorkingDaysData[$date->format('Ymd')])) {
                $min = min($weekHours[$dow], $hours);
                if ($countCurrentWorkDays && $date >= $agreement->getStartDate() && $date <= $agreement->getEndDate()) {
                    $workDay = $this->findOneByAgreementAndDate($agreement, $date);
                } else {
                    $workDay = null;
                }
                if (null === $workDay) {
                    $workDay = new WorkDay();
                    $workDay
                        ->setAgreement($agreement)
                        ->setDate(clone $date)
                        ->setHours($min);
                } elseif ($overwrite) {
                    $workDay->setHours($min);
                } else {
                    $workDay->setHours($workDay->getHours() + $min);
                }

                $this->getEntityManager()->persist($workDay);

                $collection[] = $workDay;
                $hours -= $min;
            }
            $date->add($oneMoreDay);
        }

        return $collection;
    }

    /**
     * @param \DateTime|\DateTimeImmutable $startDate
     */
    public function createWorkDayCollectionByAcademicYearGroupByMonthAndWeekNumber(
        Agreement $agreement,
        \DateTimeInterface $startDate,
        $hours,
        $weekHours,
        $overWrite,
        $ignoreNonWorkingDays
    ): array {
        $workDays = $this->createWorkDayCollectionByAcademicYear(
            $agreement,
            $startDate,
            $hours,
            $weekHours,
            $overWrite,
            $ignoreNonWorkingDays
        );

        return self::groupByMonthAndWeekNumber($workDays);
    }

    /**
     * @param WorkDay[] $list
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(WorkDay::class, 'w')
            ->where('w IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function findByYearWeekAndAgreement(string $year, $week, Agreement $agreement)
    {
        $startDate = new \DateTime();
        $startDate->setTimestamp(strtotime($year . 'W'. ($week < 10 ? '0' . $week : $week)));
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P7D'));
        return $this->createQueryBuilder('wd')
            ->where('wd.agreement = :agreement')
            ->andWhere('wd.date < :end_date')
            ->andWhere('wd.date >= :start_date')
            ->setParameter('agreement', $agreement)
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate)
            ->addOrderBy('wd.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public static function groupByMonthAndWeekNumber($workDays): array
    {
        $collection = [];

        $oneDayMore = new \DateInterval('P1D');

        /** @var WorkDay $workDay */
        foreach ($workDays as $workDay) {
            $date = $workDay->getDate();
            $month = (int) $date->format('n');
            $monthCode = (int) $date->format('Y') * 12 + $month - 1;

            if (!isset($collection[$monthCode])) {
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
            $collection[$monthCode][$currentWeek]['days'][$currentDay] = [$workDay, 0];
        }

        return $collection;
    }

    public function deleteFromAgreements($list): void
    {
        // Primero borrar los elementos con seguimiento, tanto
        // sus datos como sus actividades
        //
        // No se puede hacer desde el repositorio para evitar
        // referencias circulares
        $trackedWorkDaysId = $this->getEntityManager()
            ->createQueryBuilder()
            ->from(TrackedWorkDay::class, 'twd')
            ->select('twd.id')
            ->join('twd.agreementEnrollment', 'ae')
            ->where('ae.agreement IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->getResult();

        $this->activityTrackingRepository->deleteFromTrackedWorkDays($trackedWorkDaysId);

        $this->getEntityManager()->createQueryBuilder()
            ->delete(TrackedWorkDay::class, 'twd')
            ->where('twd IN (:list)')
            ->setParameter('list', $trackedWorkDaysId)
            ->getQuery()
            ->execute();

        $this->getEntityManager()->createQueryBuilder()
            ->delete(WorkDay::class, 'wd')
            ->where('wd.agreement IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function findPrevious(WorkDay $workDay)
    {
        return $this->createQueryBuilder('w')
            ->where('w.agreement = :agreement')
            ->andWhere('w.date < :date AND w.id != :id')
            ->setParameter('agreement', $workDay->getAgreement())
            ->setParameter('date', $workDay->getDate())
            ->setParameter('id', $workDay->getId())
            ->orderBy('w.date', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    public function findNext(WorkDay $workDay)
    {
        return $this->createQueryBuilder('w')
            ->where('w.agreement = :agreement')
            ->andWhere('w.date > :date AND w.id != :id')
            ->setParameter('agreement', $workDay->getAgreement())
            ->setParameter('date', $workDay->getDate())
            ->setParameter('id', $workDay->getId())
            ->orderBy('w.date', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }
}
