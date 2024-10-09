<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentProgram;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
}
