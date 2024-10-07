<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentProgramActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgramActivity>
 */
class StudentProgramActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProgramActivity::class);
    }
}
