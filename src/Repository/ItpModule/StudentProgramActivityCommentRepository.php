<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentProgramActivityComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgramActivityComment>
 */
class StudentProgramActivityCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProgramActivityComment::class);
    }
}
