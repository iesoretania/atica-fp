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

    public function deleteFromStudentProgramWorkcenterActivityList(array $items): void
    {
        $this->createQueryBuilder('spwac')
            ->delete()
            ->where('spwac.studentProgramActivity IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function deleteFromStudentProgramWorkcenterList(array $items): void
    {
        $comments = $this->createQueryBuilder('spwac')
            ->join('spwac.studentProgramActivity', 'spa')
            ->where('spa.studentProgramWorkcenter IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();

        $this->deleteFromList($comments);
    }

    private function deleteFromList(array $comments): void
    {
        $this->createQueryBuilder('spwac')
            ->delete()
            ->where('spwac IN (:items)')
            ->setParameter('items', $comments)
            ->getQuery()
            ->execute();
    }
}
