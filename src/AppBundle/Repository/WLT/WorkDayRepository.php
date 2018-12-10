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

namespace AppBundle\Repository\WLT;

use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\WorkDay;
use AppBundle\Repository\Edu\NonWorkingDayRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Persistence\ManagerRegistry;

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

    public function findByAgreementGroupByMonthAndWeekNumber(Agreement $agreement)
    {
        return self::groupByMonthAndWeekNumber($this->findByAgreement($agreement));
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
        $overwrite
    ) {
        $academicYear = $agreement->getStudentEnrollment()->
            getGroup()->getGrade()->getTraining()->getAcademicYear();

        $collection = new ArrayCollection();
        $count = 0;
        foreach ($weekHours as $hourCount) {
            $count += $hourCount;
        }

        if ($count <= 0) {
            return $collection;
        }

        $date = new \DateTime(
            $startDate->format('Y') . '-' . $startDate->format('m') . '-' . $startDate->format('d'),
            new \DateTimeZone('UTC')
        );

        while ($hours > 0) {
            $dow = $date->format('N') - 1;

            if ($weekHours[$dow] > 0) {
                $nonWorkingDay = $this->nonWorkingDayRepository->findOneByAcademicYearAndDate($academicYear, $date);
                if (null === $nonWorkingDay) {
                    $min = min($weekHours[$dow], $hours);
                    $workDay = $this->findOneByAgreementAndDate($agreement, $date);
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

                    $collection[] = $workDay;
                    $hours -= $min;
                }
            }
            $date->add(new \DateInterval('P1D'));
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
        foreach ($workDays as $workDay) {
            $date= $workDay->getDate();
            $dow = $date->format('N') - 1;
            $week = $date->format('W');
            $month = (int) $date->format('Y') * 12 + (int) $date->format('n') - 1;

            $count = isset($collection[$month][$week]['days']) ?
                count($collection[$month][$week]['days']) : 0;
            $count = $dow - $count;

            while ($count-- > 0) {
                $collection[$month][$week]['days'][] = [];
            }
            $collection[$month][$week]['days'][] = $workDay;
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
}
