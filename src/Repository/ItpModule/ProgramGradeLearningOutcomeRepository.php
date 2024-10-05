<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGradeLearningOutcome;
use App\Form\Model\ItpModule\CustomProgramGradeLearningOutcome;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramGradeLearningOutcome>
 */
class ProgramGradeLearningOutcomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly \App\Repository\Edu\LearningOutcomeRepository $learningOutcomeRepository)
    {
        parent::__construct($registry, ProgramGradeLearningOutcome::class);
    }

    public function persist(ProgramGradeLearningOutcome $activityLearningOutcome): void
    {
        $this->getEntityManager()->persist($activityLearningOutcome);
    }

    public function generateByProgramGradeAndSubjects(ProgramGrade $programGrade, $subjects): array
    {
        $current = $programGrade->getProgramGradeLearningOutcomes();

        $allLearningOutcomes = $this->learningOutcomeRepository->findBySubjects($subjects);
        $result = [];
        foreach ($allLearningOutcomes as $learningOutcome) {
            $found = false;
            $pglo = null;
            foreach ($current as $item) {
                if ($item->getLearningOutcome()->getId() === $learningOutcome->getId()) {
                    $found = true;
                    $pglo = $item;
                    break;
                }
            }
            if (!$found) {
                $pglo = new ProgramGradeLearningOutcome();
                $pglo
                    ->setProgramGrade($programGrade)
                    ->setLearningOutcome($learningOutcome);
            }

            $custom = new CustomProgramGradeLearningOutcome();
            $custom->setProgramGradeLearningOutcome($pglo);
            if (!$found) {
                $custom->setSelected(0);
            } else {
                $custom->setSelected($pglo->isShared() ? 1 : 2);
            }
            $result[$pglo->getLearningOutcome()->getId()] = $custom;
        }
        return $result;
    }

    public function remove(ProgramGradeLearningOutcome $programGradeLearningOutcome)
    {
        $this->getEntityManager()->remove($programGradeLearningOutcome);
    }

    public function flush()
    {
        $this->getEntityManager()->flush();
    }

    public function deleteFromProgramGradeList($items)
    {
        $this->createQueryBuilder('pglo')
            ->delete()
            ->where('pglo.programGrade IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
