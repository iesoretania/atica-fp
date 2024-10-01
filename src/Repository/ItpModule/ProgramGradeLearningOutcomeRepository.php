<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\ProgramGradeLearningOutcome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramGradeLearningOutcome>
 */
class ProgramGradeLearningOutcomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProgramGradeLearningOutcome::class);
    }

    public function persist(ProgramGradeLearningOutcome $activityLearningOutcome): void
    {
        $this->getEntityManager()->persist($activityLearningOutcome);
    }
}
