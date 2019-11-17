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

namespace AppBundle\Repository\WLT;

use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\WorkDay;
use AppBundle\Repository\Edu\NonWorkingDayRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

class WorkDayRepository extends ServiceEntityRepository
{
    /** @var NonWorkingDayRepository $nonWorkingDayRepository */
    private $nonWorkingDayRepository;

    public function __construct(ManagerRegistry $registry, NonWorkingDayRepository $nonWorkingDayRepository)
    {
        parent::__construct($registry, WorkDay::class);
        $this->nonWorkingDayRepository = $nonWorkingDayRepository;
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

    public function findAndCountByAgreement(Agreement $agreement)
    {
        return $this->createQueryBuilder('wr')
            ->addSelect('COUNT(ar)')
            ->leftJoin('wr.activityRealizations', 'ar')
            ->where('wr.agreement = :agreement')
            ->setParameter('agreement', $agreement)
            ->addOrderBy('wr.date')
            ->groupBy('wr')
            ->getQuery()
            ->getResult();
    }

    public function hoursStatsByAgreement(Agreement $agreement)
    {
        try {
            return $this->createQueryBuilder('wd')
                ->select('SUM(wd.hours)')
                ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked * wd.hours ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN wd.absence = 1 THEN wd.hours ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN wd.hours ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN wd.locked = 1 THEN wd.hours ELSE 0 END)')
                ->addSelect('COUNT(wd)')
                ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN wd.absence = 1 THEN 1 ELSE 0 END)')
                ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN 1 ELSE 0 END)')
                ->addSelect('SUM(wd.locked)')
                ->join('wd.agreement', 'a')
                ->where('wd .agreement = :agreement')
                ->setParameter('agreement', $agreement)
                ->groupBy('a')
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $e) {
            return [];
        }
    }

    /**
     * @param array $list
     * @param Agreement $agreement
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

    public function findOneByAgreementAndDate(Agreement $agreement, \DateTime $date)
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

    /**
     * @param Agreement $agreement
     * @return int
     */
    public function countByAgreement(Agreement $agreement)
    {
        try {
            return $this->createQueryBuilder('wr')
                ->select('COUNT(wr)')
                ->where('wr.agreement = :agreement')
                ->setParameter('agreement', $agreement)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }
        return 0;
    }

    public function findByAgreementGroupByMonthAndWeekNumber(Agreement $agreement)
    {
        return self::groupByMonthAndWeekNumber($this->findAndCountByAgreement($agreement));
    }

    /**
     * @param Agreement $agreement
     * @param \DateTime $startDate
     * @param int $hours
     * @param int[] $weekHours
     * @param bool $overwrite
     *
     * @return WorkDay[]|Collection
     */
    public function createWorkDayCollectionByAcademicYear(
        Agreement $agreement,
        \DateTime $startDate,
        $hours,
        $weekHours,
        $overwrite = false
    ) {
        $academicYear = $agreement->getStudentEnrollment()->
            getGroup()->getGrade()->getTraining()->getAcademicYear();

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

        $nonWorkingDays = $this->nonWorkingDayRepository->findByAcademicYear($academicYear);
        $nonWorkingDaysData = [];

        foreach ($nonWorkingDays as $nonWorkingDay) {
            $nonWorkingDaysData[$nonWorkingDay->getDate()->format('Ymd')] = 1;
        }

        while ($hours > 0) {
            $dow = (int) $date->format('N') - 1;
            if ($weekHours[$dow] > 0 && false === isset($nonWorkingDaysData[$date->format('Ymd')])) {
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
                        ->setHours($min)
                        ->setStartTime1($agreement->getDefaultStartTime1())
                        ->setEndTime1($agreement->getDefaultEndTime1())
                        ->setStartTime2($agreement->getDefaultStartTime2())
                        ->setEndTime2($agreement->getDefaultEndTime2());
                } elseif ($overwrite) {
                    $workDay->setHours($min);
                } else {
                    $workDay->setHours($workDay->getHours() + $min);
                }

                $this->getEntityManager()->persist($workDay);

                $collection[] = [$workDay, 0];
                $hours -= $min;
            }
            $date->add($oneMoreDay);
        }

        return $collection;
    }

    /**
     * @param Agreement $agreement
     * @param \DateTime $startDate
     * @param int $hours
     * @param int[] $weekHours
     * @param bool $overWrite
     *
     * @return WorkDay[]
     */
    public function createWorkDayCollectionByAcademicYearGroupByMonthAndWeekNumber(
        Agreement $agreement,
        \DateTime $startDate,
        $hours,
        $weekHours,
        $overWrite
    ) {
        $workDays = $this->createWorkDayCollectionByAcademicYear(
            $agreement,
            $startDate,
            $hours,
            $weekHours,
            $overWrite
        );

        return self::groupByMonthAndWeekNumber($workDays);
    }

    /**
     * @param WorkDay[] $workDays
     * @return array
     */
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
     * @param WorkDay[]
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

    public function findUnfilledWorkDaysBeforeDateByAgreement(Agreement $agreement, \DateTime $date)
    {
        return $this->createQueryBuilder('wd')
            ->where('wd.agreement = :agreement')
            ->andWhere('SIZE(wd.activityRealizations) = 0')
            ->andWhere('wd.date < :date')
            ->andWhere('wd.absence = :absence')
            ->setParameter('agreement', $agreement)
            ->setParameter('date', $date)
            ->setParameter('absence', WorkDay::NO_ABSENCE)
            ->addOrderBy('wd.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param WorkDay[]
     * @param bool
     * @return mixed
     */
    public function updateAttendance($list, $value)
    {
        /** @var WorkDay $workDay */
        foreach ($list as $workDay) {
            $workDay->setActivityRealizations([]);
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->update(WorkDay::class, 'w')
            ->set('w.absence', ':value')
            ->where('w IN (:list)')
            ->andWhere('w.locked = :locked')
            ->setParameter('list', $list)
            ->setParameter('value', $value)
            ->setParameter('locked', false)
            ->getQuery()
            ->execute();
    }

    /**
     * @param WorkDay[]
     * @param bool
     * @return mixed
     */
    public function updateLock($list, $value)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->update(WorkDay::class, 'w')
            ->set('w.locked', ':value')
            ->where('w IN (:list)')
            ->setParameter('list', $list)
            ->setParameter('value', $value)
            ->getQuery()
            ->execute();
    }

    public function findByStudentEnrollment(StudentEnrollment $studentEnrollment)
    {
        return $this->createQueryBuilder('wr')
            ->join('wr.agreement', 'a')
            ->where('a.studentEnrollment = :student_enrollment')
            ->setParameter('student_enrollment', $studentEnrollment)
            ->addOrderBy('wr.date')
            ->getQuery()
            ->getResult();
    }
}
