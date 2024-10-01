<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentLearningProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentLearningProgram>
 */
class StudentLearningProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentLearningProgram::class);
    }
}
