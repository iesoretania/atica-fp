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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StudentProgram::class);
    }

    public function createByProgramGroupQueryBuilder(ProgramGroup $programGroup, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('slp')
            ->addSelect('se', 's', 'w', 'c', 'pg', 'g')
            ->join('slp.studentEnrollment', 'se')
            ->join('se.person', 's')
            ->join('slp.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('slp.programGroup', 'pg')
            ->join('pg.group', 'g')
            ->where('pg = :programGroup')
            ->setParameter('programGroup', $programGroup);

        if ($q) {
            $qb
                ->andWhere('s.firstName LIKE :tq OR s.lastName LIKE :tq OR g.name LIKE :tq OR w.name LIKE :tq OR c.name LIKE :tq')
                ->setParameter('tq', "%" . $q . "%");
        }

        return $qb;
    }

    public function persist(StudentProgram $studentProgram): void
    {
        $this->getEntityManager()->persist($studentProgram);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
