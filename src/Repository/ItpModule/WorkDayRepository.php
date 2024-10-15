<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\WorkDay;
use App\Repository\Edu\NonWorkingDayRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        return self::groupByMonthAndWeekNumber($this->getStatsByStudentProgramWorkcenter($studentProgramWorkcenter));
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

    public function findByStudentProgramWorkcenters(array $items): array
    {
        return $this->createQueryBuilder('wd')
            ->where('wd.studentProgramWorkcenter IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }
}
