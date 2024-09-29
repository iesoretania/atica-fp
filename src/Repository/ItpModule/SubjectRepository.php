<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\Subject;
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\ProgramGrade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Subject>
 */
class SubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subject::class);
    }

    public function findByActivity(Activity $activity)
    {
        return $this->createQueryBuilder('s')
            ->join('s.grade', 'g')
            ->join(ProgramGrade::class, 'pg', 'WITH', 'pg.grade = g')
            ->join('pg.activities', 'a')
            ->join('a.assignedLearningOutcomes', 'ac')
            ->join('ac.criteria', 'c')
            ->join('c.learningOutcome', 'lo')
            ->where('lo.subject = s')
            ->andWhere('a = :activity')
            ->setParameter('activity', $activity)
            ->getQuery()
            ->getResult();
    }
}
