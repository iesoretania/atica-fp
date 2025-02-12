<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use App\Entity\ItpModule\WorkDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgramWorkcenterActivity>
 */
class StudentProgramWorkcenterActivityRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly StudentProgramWorkcenterActivityCommentRepository $studentProgramWorkcenterActivityCommentRepository
    ) {
        parent::__construct($registry, StudentProgramWorkcenterActivity::class);
    }

    final public function createFindByStudentProgramWorkcenterOrderByCodeQueryBuilder(StudentProgramWorkcenter $studentProgramWorkcenter): QueryBuilder
    {
        return $this->createQueryBuilder('spa')
            ->join('spa.activity', 'a')
            ->where('spa.studentProgramWorkcenter = :studentProgramWorkcenter')
            ->setParameter('studentProgramWorkcenter', $studentProgramWorkcenter)
            ->orderBy('LENGTH(a.code)', 'ASC')
            ->addOrderBy('a.code', 'ASC')
            ->addOrderBy('a.name', 'ASC');
    }

    final public function findByStudentProgramWorkcenterOrderByCode(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createFindByStudentProgramWorkcenterOrderByCodeQueryBuilder($studentProgramWorkcenter)
            ->getQuery()
            ->getResult();
    }

    final public function findScaleValueSubmittedByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createFindByStudentProgramWorkcenterOrderByCodeQueryBuilder($studentProgramWorkcenter)
            ->join(WorkDay::class, 'wd', 'WITH', 'a MEMBER OF wd.activities AND wd.studentProgramWorkcenter = spa.studentProgramWorkcenter')
            ->getQuery()
            ->getResult();
    }

    final public function findSubmittedByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createQueryBuilder('spa')
            ->distinct()
            ->join('spa.activity', 'a')
            ->join(WorkDay::class, 'wd', 'WITH', 'a MEMBER OF wd.activities AND wd.studentProgramWorkcenter = spa.studentProgramWorkcenter')
            ->where('spa.studentProgramWorkcenter = :studentProgramWorkcenter')
            ->setParameter('studentProgramWorkcenter', $studentProgramWorkcenter)
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

    public function deleteFromListByStudentProgramWorkcenter(array $selectedItems): void
    {
        $this->studentProgramWorkcenterActivityCommentRepository->deleteFromStudentProgramWorkcenterList($selectedItems);
        $this->createQueryBuilder('spa')
            ->delete()
            ->where('spa.studentProgramWorkcenter IN (:selectedItems)')
            ->setParameter('selectedItems', $selectedItems)
            ->getQuery()
            ->execute();
    }

    public function findByStudentPrograms(array $items): array
    {
        return $this->createQueryBuilder('spa')
            ->join('spa.studentProgramWorkcenter', 'spw')
            ->join('spw.studentProgram', 'sp')
            ->where('sp IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList(array $items): void
    {
        $this->studentProgramWorkcenterActivityCommentRepository->deleteFromStudentProgramWorkcenterActivityList($items);
        $this->createQueryBuilder('spa')
            ->delete()
            ->where('spa IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function deleteFromActivityList(array $items): void
    {
        $this->createQueryBuilder('spa')
            ->delete()
            ->where('spa.activity IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function findByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createQueryBuilder('spa')
            ->addSelect('a', 'sv', 'c')
            ->join('spa.activity', 'a')
            ->leftJoin('spa.scaleValue', 'sv')
            ->leftJoin('spa.comments', 'c')
            ->where('spa.studentProgramWorkcenter = :studentProgramWorkcenter')
            ->setParameter('studentProgramWorkcenter', $studentProgramWorkcenter)
            ->getQuery()
            ->getResult();
    }
}
