<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentProgramWorkcenterActivityComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgramWorkcenterActivityComment>
 */
class StudentProgramWorkcenterActivityCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProgramWorkcenterActivityComment::class);
    }
}
