<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\WorkDay;
use App\Repository\Edu\NonWorkingDayRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkDay>
 */
class WorkDayRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly NonWorkingDayRepository $nonWorkingDayRepository)
    {
        parent::__construct($registry, WorkDay::class);
    }

    final public function getCalendarByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        $data = $this->getStatsByStudentProgramWorkcenter($studentProgramWorkcenter);
        return self::groupByMonthAndWeekNumber($data);
    }

    public static function groupByMonthAndWeekNumber(array $workDaysStats): array
    {
        $collection = [];

        $oneDayMore = new \DateInterval('P1D');

        foreach ($workDaysStats as $workDayData) {
            $workDay = $workDayData[0];
            assert($workDay instanceof WorkDay);
            $date = $workDay->getDate();
            assert($date instanceof \DateTimeInterface);
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
            $collection[$monthCode][$currentWeek]['days'][$currentDay] = $workDayData;
        }

        return $collection;
    }

    private function getStatsByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createQueryBuilder('wd')
            ->addSelect('SIZE(wd.activities) AS activity_count')
            ->where('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
            ->addOrderBy('wd.date')
            ->getQuery()
            ->getResult();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function getStudentProgramWorkcenterStats(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        $stats = $this->createQueryBuilder('wd')
            ->select('MIN(wd.date) AS startDate, MAX(wd.date) AS endDate')
            ->where('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
            ->getQuery()
            ->getOneOrNullResult();

        return $stats ?: [];
    }

    final public function createWorkDayCollectionByStudentProgramWorkcenterGroupByMonthAndWeekNumber(
        StudentProgramWorkcenter $studentProgramWorkcenter,
        \DateTimeInterface $startDate,
        int $hours,
        array $weekHours,
        bool $overwrite,
        bool $ignoreNonWorkingDays
    ): array
    {
        static $timezone = new \DateTimeZone('UTC');

        $academicYear = $studentProgramWorkcenter->getStudentProgram()->getStudentEnrollment()->
            getGroup()->getGrade()->getTraining()->getAcademicYear();
        assert($academicYear instanceof AcademicYear);

        $currentWorkDays = $this->findByStudentProgramWorkcenter($studentProgramWorkcenter);
        $dataCurrentWorkDays = [];
        foreach ($currentWorkDays as $currentWorkDay) {
            $dataCurrentWorkDays[$currentWorkDay->getDate()->format('Y-m-d')] = $currentWorkDay;
        }

        $collection = [];

        $count = array_reduce($weekHours, fn($carry, $item) => $carry + $item, 0);

        // Necesitamos que en el array haya al menos que repartir una hora semanal
        if ($count <= 0) {
            return $collection;
        }

        $date = new \DateTime(
            $startDate->format('Y-m-d') . ' 00:00:00',
            $timezone
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
                $workDay = $dataCurrentWorkDays[$date->format('Y-m-d')] ?? null;
                if (!$workDay instanceof WorkDay) {
                    $workDay = new WorkDay();
                    $workDay
                        ->setStudentProgramWorkcenter($studentProgramWorkcenter)
                        ->setDate(clone $date)
                        ->setHours($min);
                        /*->setStartTime1($studentProgramWorkcenter->getDefaultStartTime1())
                        ->setEndTime1($studentProgramWorkcenter->getDefaultEndTime1())
                        ->setStartTime2($studentProgramWorkcenter->getDefaultStartTime2())
                        ->setEndTime2($studentProgramWorkcenter->getDefaultEndTime2());*/
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

    public function findByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createQueryBuilder('wd')
            ->where('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
            ->addOrderBy('wd.date')
            ->getQuery()
            ->getResult();
    }

    final public function findInListByIdAndStudentProgramWorkcenter(array $items, StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createQueryBuilder('wd')
            ->addSelect('wd')
            ->where('wd.id IN (:items)')
            ->andWhere('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->setParameter('items', $items)
            ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
            ->getQuery()
            ->getResult();
    }

    final public function deleteFromList(array $workDays): void
    {
        $this->createQueryBuilder('wd')
            ->delete()
            ->where('wd IN (:work_days)')
            ->setParameter('work_days', $workDays)
            ->getQuery()
            ->execute();
    }

    final public function findByStudentProgramWorkcenters(array $items): array
    {
        return $this->createQueryBuilder('wd')
            ->where('wd.studentProgramWorkcenter IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    final public function countHoursByStudentProgram(StudentProgramWorkcenter $studentProgramWorkcenter): int
    {
        $hours = $this->createQueryBuilder('wd')
            ->select('SUM(wd.hours)')
            ->where('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
            ->getQuery()
            ->getSingleScalarResult();
        return $hours ?? 0;
    }

    final public function findOneByStudentProgramWorkcenterAndDate(StudentProgramWorkcenter $target, \DateTimeImmutable $date): ?WorkDay
    {
        return $this->createQueryBuilder('wd')
            ->where('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->andWhere('wd.date = :date')
            ->setParameter('student_program_workcenter', $target)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();
    }

    final public function hoursStatsByStudentProgram(StudentProgramWorkcenter $studentProgramWorkcenter): array
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
                ->where('wd.studentProgramWorkcenter = :student_program_workcenter')
                ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
                ->groupBy('wd.studentProgramWorkcenter')
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException) {
            return [];
        }
    }

    final public function findPrevious(WorkDay $workDay): ?WorkDay
    {
        return $this->createQueryBuilder('w')
            ->where('w.studentProgramWorkcenter = :student_program_workcenter')
            ->andWhere('w.date < :date AND w.id != :id')
            ->setParameter('student_program_workcenter', $workDay->getStudentProgramWorkcenter())
            ->setParameter('date', $workDay->getDate())
            ->setParameter('id', $workDay->getId())
            ->orderBy('w.date', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    final public function findNext(WorkDay $workDay): ?Workday
    {
        return $this->createQueryBuilder('w')
            ->where('w.studentProgramWorkcenter = :student_program_workcenter')
            ->andWhere('w.date > :date AND w.id != :id')
            ->setParameter('student_program_workcenter', $workDay->getStudentProgramWorkcenter())
            ->setParameter('date', $workDay->getDate())
            ->setParameter('id', $workDay->getId())
            ->orderBy('w.date', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    final public function findByYearWeekAndAgreement(int $year, int $week, StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        $startDate = new \DateTime();
        $startDate->setTimestamp(strtotime($year . 'W'. ($week < 10 ? '0' . $week : $week)));
        $endDate = clone $startDate;
        $endDate->add(new \DateInterval('P7D'));
        return $this->createQueryBuilder('wd')
            ->where('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->andWhere('wd.date < :end_date')
            ->andWhere('wd.date >= :start_date')
            ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
            ->setParameter('start_date', $startDate)
            ->setParameter('end_date', $endDate)
            ->addOrderBy('wd.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    final public function updateLock(array $list, StudentProgramWorkcenter $studentProgramWorkcenter, bool $value): void
    {
        $this->getEntityManager()->createQueryBuilder()
            ->update(WorkDay::class, 'wd')
            ->set('wd.locked', ':value')
            ->where('wd IN (:list)')
            ->andWhere('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->setParameter('list', $list)
            ->setParameter('value', $value)
            ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
            ->getQuery()
            ->execute();
    }

    public function updateWeekLock(int $year, int $week, StudentProgramWorkcenter $studentProgramWorkcenter, bool $value): void
    {
        $items = $this->findByYearWeekAndAgreement($year, $week, $studentProgramWorkcenter);
        $this->updateLock($items, $studentProgramWorkcenter, $value);
    }

    final public function updateAttendance(array $list, StudentProgramWorkcenter $studentProgramWorkcenter, int $value): void
    {
        if ($value !== WorkDay::ABSENCE_NONE) {
            foreach ($list as $workDay) {
                if (!$workDay->isLocked()) {
                    $workDay->getActivities()->clear();
                    $workDay->setOtherActivities(null);
                }
            }
        }
        $this->getEntityManager()->createQueryBuilder()
            ->update(WorkDay::class, 'wd')
            ->set('wd.absence', ':value')
            ->where('wd IN (:list)')
            ->andWhere('wd.locked = false')
            ->andWhere('wd.studentProgramWorkcenter = :student_program_workcenter')
            ->setParameter('list', $list)
            ->setParameter('value', $value)
            ->setParameter('student_program_workcenter', $studentProgramWorkcenter)
            ->getQuery()
            ->execute();
    }
}
