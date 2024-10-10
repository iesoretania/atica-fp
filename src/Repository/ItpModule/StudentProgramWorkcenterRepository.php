<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgram>
 */
class StudentProgramWorkcenterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly WorkDayRepository $workDayRepository)
    {
        parent::__construct($registry, StudentProgramWorkcenter::class);
    }

    public function deleteFromStudentProgramList(array $items): void
    {
        $this->createQueryBuilder('spw')
            ->delete()
            ->where('spw.studentProgram IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function createByStudentProgramQueryBuilder(StudentProgram $studentProgram, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('spw')
            ->addSelect('spw', 'c', 'w')
            ->join('spw.workcenter', 'w')
            ->join('w.company', 'c')
            ->where('spw.studentProgram = :studentProgram')
            ->setParameter('studentProgram', $studentProgram)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('w.name', 'ASC');

        if ($q) {
            $qb
                ->andWhere('w.name LIKE :tq OR c.name LIKE :tq')
                ->setParameter('tq', "%" . $q . "%");
        }

        return $qb;
    }

    public function persist(StudentProgramWorkcenter $studentProgramWorkcenter): void
    {
        $this->getEntityManager()->persist($studentProgramWorkcenter);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    final public function findAllInListByIdAndStudentProgram(array $items, StudentProgram $studentProgram): array
    {
        return $this->createQueryBuilder('spw')
            ->addSelect('spw', 'c', 'w', 'p', 'g', 'sp', 'se')
            ->join('spw.studentProgram', 'sp')
            ->join('sp.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('spw.workcenter', 'w')
            ->join('w.company', 'c')
            ->where('spw.id IN (:items)')
            ->andWhere('spw.studentProgram = :studentProgram')
            ->setParameter('items', $items)
            ->setParameter('studentProgram', $studentProgram)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('w.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    final public function deleteFromList(array $selectedItems): void
    {
        $this->createQueryBuilder('spw')
            ->delete()
            ->where('spw.id IN (:selectedItems)')
            ->setParameter('selectedItems', $selectedItems)
            ->getQuery()
            ->execute();
    }

    final public function updateDates(StudentProgramWorkcenter $studentProgramWorkcenter): void
    {
        static $timezone = new \DateTimeZone('UTC');
        $stats = $this->workDayRepository->getStudentProgramWorkcenterStats($studentProgramWorkcenter);
        if (isset($stats['endDate'], $stats['startDate'])) {
            $studentProgramWorkcenter->setStartDate(new \DateTimeImmutable($stats['startDate'], $timezone));
            $studentProgramWorkcenter->setEndDate(new \DateTimeImmutable($stats['endDate'], $timezone));
        }
    }
}
