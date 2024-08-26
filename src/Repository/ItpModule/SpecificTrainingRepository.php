<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\SpecificTraining;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SpecificTraining>
 */
class SpecificTrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SpecificTraining::class);
    }
}
