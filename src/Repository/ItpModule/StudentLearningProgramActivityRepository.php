<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentLearningProgramActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentLearningProgramActivity>
 */
class StudentLearningProgramActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentLearningProgramActivity::class);
    }
}
