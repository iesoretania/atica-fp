<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\TrainingProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramGrade>
 */
class ProgramGradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgramGrade::class);
    }

    public function findByTrainingProgram(TrainingProgram $trainingProgram): array
    {
        return $this->createQueryBuilder('pg')
            ->andWhere('pg.trainingProgram = :trainingProgram')
            ->join('pg.grade', 'g')
            ->setParameter('trainingProgram', $trainingProgram)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function persist(ProgramGrade $programGrade): void
    {
        $this->getEntityManager()->persist($programGrade);
    }

    public function flush()
    {
        $this->getEntityManager()->flush();
    }
}
