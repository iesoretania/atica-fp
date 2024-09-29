<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\ActivityLearningOutcome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLearningOutcome>
 */
class ActivityLearningOutcomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLearningOutcome::class);
    }

    public function persist(ActivityLearningOutcome $activityLearningOutcome): void
    {
        $this->getEntityManager()->persist($activityLearningOutcome);
    }
}
