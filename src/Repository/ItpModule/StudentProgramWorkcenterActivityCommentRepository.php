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

    final public function persist(StudentProgramWorkcenterActivityComment $comment): void
    {
        $this->getEntityManager()->persist($comment);
    }

    final public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    final public function remove(StudentProgramWorkcenterActivityComment $studentProgramWorkcenterActivityComment): void
    {
        $this->getEntityManager()->remove($studentProgramWorkcenterActivityComment);
    }
}
