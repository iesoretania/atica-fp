<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StudentProgram>
 */
class StudentProgramRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry                                     $registry,
        private readonly StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository
    ) {
        parent::__construct($registry, StudentProgram::class);
    }

    public function createByProgramGroupQueryBuilder(ProgramGroup $programGroup, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('slp')
            ->addSelect('se', 's', 'pg', 'g')
            ->join('slp.studentEnrollment', 'se')
            ->join('se.person', 's')
            ->join('slp.programGroup', 'pg')
            ->join('pg.group', 'g')
            ->where('pg = :programGroup')
            ->setParameter('programGroup', $programGroup);

        if ($q) {
            $qb
                ->andWhere('s.firstName LIKE :tq OR s.lastName LIKE :tq OR g.name LIKE :tq')
                ->setParameter('tq', "%" . $q . "%");
        }

        return $qb;
    }

    public function findByProgramGroupAndQuery(ProgramGroup $programGroup, ?string $q): array
    {
        return $this->createByProgramGroupQueryBuilder($programGroup, $q)
            ->getQuery()
            ->getResult();
    }

    public function persist(StudentProgram $studentProgram): void
    {
        $this->getEntityManager()->persist($studentProgram);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    final public function deleteFromList(array $items): void
    {
        $this->studentProgramWorkcenterRepository->deleteFromStudentProgramList($items);
        $this->createQueryBuilder('slp')
            ->delete()
            ->where('slp IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    final public function findAllInListByIdAndProgramGroup(array $items, ProgramGroup $programGroup): array
    {
        return $this->createQueryBuilder('sp')
            ->join('sp.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->where('sp.programGroup = :program_group')
            ->andWhere('sp.id IN (:items)')
            ->setParameter('program_group', $programGroup)
            ->setParameter('items', $items)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromProgramGradeList(array $items)
    {
        $items = $this->findByProgramGradeList($items);
        $this->studentProgramWorkcenterRepository->deleteFromStudentProgramList($items);
        $this->createQueryBuilder('sp')
            ->delete()
            ->where('sp IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    private function findByProgramGradeList(array $items): array
    {
        return $this->createQueryBuilder('sp')
            ->join('sp.programGroup', 'pg')
            ->where('pg.programGrade IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }
}
