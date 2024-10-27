<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgramWorkcenterActivity>
 */
class StudentProgramWorkcenterActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProgramWorkcenterActivity::class);
    }

    final public function createFindByStudentProgramWorkcenterOrderByCodeQueryBuilder(StudentProgramWorkcenter $studentProgramWorkcenter): QueryBuilder
    {
        return $this->createQueryBuilder('spa')
            ->join('spa.activity', 'a')
            ->where('spa.studentProgramWorkcenter = :studentProgramWorkcenter')
            ->setParameter('studentProgramWorkcenter', $studentProgramWorkcenter)
            ->orderBy('a.code', 'ASC')
            ->addOrderBy('a.name', 'ASC');
    }

    final public function findByStudentProgramWorkcenterOrderByCode(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createFindByStudentProgramWorkcenterOrderByCodeQueryBuilder($studentProgramWorkcenter)
            ->getQuery()
            ->getResult();
    }

    final public function findSubmittedByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createFindByStudentProgramWorkcenterOrderByCodeQueryBuilder($studentProgramWorkcenter)
            ->andWhere('spa.scaleValue IS NOT NULL')
            ->getQuery()
            ->getResult();
    }

    final public function remove(StudentProgramWorkcenterActivity $activity): void
    {
        $this->getEntityManager()->remove($activity);
    }

    final public function persist(StudentProgramWorkcenterActivity $studentProgramWorkcenterActivity): void
    {
        $this->getEntityManager()->persist($studentProgramWorkcenterActivity);
    }
}
