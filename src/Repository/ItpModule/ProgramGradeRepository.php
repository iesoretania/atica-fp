<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Entity\Edu\Subject;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\TrainingProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramGrade>
 */
class ProgramGradeRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry                                        $registry,
        private readonly ActivityRepository                    $activityRepository,
        private readonly ProgramGroupRepository                $programGroupRepository,
        private readonly CompanyProgramRepository              $companyProgramRepository,
        private readonly StudentProgramRepository              $studentProgramRepository
    )
    {
        parent::__construct($registry, ProgramGrade::class);
    }

    public function persist(ProgramGrade $programGrade): void
    {
        $this->getEntityManager()->persist($programGrade);
    }

    public function flush()
    {
        $this->getEntityManager()->flush();
    }

    public function findByTrainingProgram(TrainingProgram $trainingProgram): array
    {
        return $this->createQueryBuilder('pg')
            ->andWhere('pg.trainingProgram = :trainingProgram')
            ->join('pg.grade', 'g')
            ->setParameter('trainingProgram', $trainingProgram)
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getProgramGradesStatsByTrainingProgram(TrainingProgram $trainingProgram): array
    {
        return $this->createQueryBuilder('pg')
            ->addSelect('COUNT(DISTINCT a) AS total_activities')
            ->addSelect('COUNT(DISTINCT s) AS total_subjects')
            ->addSelect('COUNT(DISTINCT lo) AS total_learning_outcomes')
            ->addSelect('COUNT(DISTINCT c) AS total_criteria')
            ->addSelect('COUNT(DISTINCT xs) AS subjects')
            ->addSelect('COUNT(DISTINCT xlo) AS learning_outcomes')
            ->addSelect('COUNT(DISTINCT xc) AS criteria')
            ->andWhere('pg.trainingProgram = :trainingProgram')
            ->join('pg.trainingProgram', 'tp')
            ->join('pg.grade', 'g')
            ->leftJoin('pg.activities', 'a')
            ->leftJoin('a.assignedLearningOutcomes', 'xc')
            ->leftJoin('xc.learningOutcome', 'xlo')
            ->leftJoin('xlo.subject', 'xs')
            ->leftJoin('g.subjects', 's')
            ->leftJoin('s.learningOutcomes', 'lo')
            ->leftJoin('lo.criteria', 'c')
            ->leftJoin('c.learningOutcome', 'ac')
            ->setParameter('trainingProgram', $trainingProgram)
            ->groupBy('pg')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromTrainingProgramList(array $items): void
    {
        foreach ($items as $item) {
            assert($item instanceof TrainingProgram);
            $this->deleteFromList($this->findByTrainingProgram($item));
        }
    }

    public function getStatsByTrainingProgram(TrainingProgram $trainingProgram): array
    {
        return $this->createQueryBuilder('pg')
            ->select('pg as program_grade')
            ->addSelect('COUNT(DISTINCT a) AS total_activities')
            ->addSelect('COUNT(DISTINCT s) AS total_subjects')
            ->addSelect('COUNT(DISTINCT lo) AS total_learning_outcomes')
            ->addSelect('COUNT(DISTINCT c) AS total_criteria')
            ->addSelect('COUNT(DISTINCT ac) AS activity_criteria')
            ->addSelect('COUNT(DISTINCT alo) AS activity_learning_outcomes')
            ->addSelect('COUNT(DISTINCT asu) AS activity_subjects')
            ->join('pg.grade', 'g')
            ->leftJoin('pg.activities', 'a')
            ->leftJoin('a.criteria', 'ac')
            ->leftJoin('ac.learningOutcome', 'alo')
            ->leftJoin('alo.subject', 'asu')
            ->leftJoin(Subject::class, 's', 'WITH', 'g = s.grade')
            ->leftJoin(LearningOutcome::class, 'lo', 'WITH', 's = lo.subject')
            ->leftJoin(Criterion::class, 'c', 'WITH', 'lo = c.learningOutcome')
            ->andWhere('pg.trainingProgram = :trainingProgram')
            ->setParameter('trainingProgram', $trainingProgram)
            ->groupBy('pg')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList(array $items): void
    {
        $this->studentProgramRepository->deleteFromProgramGradeList($items);
        $this->activityRepository->deleteFromProgramGradeList($items);
        $this->companyProgramRepository->deleteFromProgramGradeList($items);
        $this->programGroupRepository->deleteFromProgramGradeList($items);

        $this->createQueryBuilder('pg')
            ->delete()
            ->where('pg IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
