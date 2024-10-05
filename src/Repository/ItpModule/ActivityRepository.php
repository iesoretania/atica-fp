<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function createActivityByProgramGradeQueryBuilder(ProgramGrade $programGrade, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('a')
            ->addSelect('c', 'lo')
            ->andWhere('a.programGrade = :programGrade')
            ->leftJoin('a.criteria', 'c')
            ->leftJoin('c.learningOutcome', 'lo')
            ->setParameter('programGrade', $programGrade)
            ->orderBy('a.code', 'ASC')
            ->addOrderBy('lo.code', 'ASC')
            ->addOrderBy('c.code', 'ASC');

        if ($q) {
            $qb
                ->andWhere('a.name LIKE :tq OR a.description LIKE :tq OR a.code LIKE :tq'
                    . ' OR c.code LIKE :tq OR c.name LIKE :tq'
                    . ' OR lo.code LIKE :tq OR lo.description LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }

        return $qb;
    }

    public function persist(Activity $activity): void
    {
        $this->getEntityManager()->persist($activity);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
