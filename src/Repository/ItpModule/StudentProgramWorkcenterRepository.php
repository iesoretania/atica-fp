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
    public function __construct(ManagerRegistry $registry)
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
}
