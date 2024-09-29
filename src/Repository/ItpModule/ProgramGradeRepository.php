<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\LearningOutcome;
use App\Entity\ItpModule\ActivityLearningOutcome;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\TrainingProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProgramGrade>
 */
class ProgramGradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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

    public function getProgramGradesStatsByTrainingProgram(TrainingProgram $trainingProgram)
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

    public function getProgramGradesWeightStatsByTrainingProgram(TrainingProgram $trainingProgram)
    {
        $learningOutcomes = $this->getEntityManager()->createQueryBuilder()
            ->from(LearningOutcome::class, 'lo')
            ->select('lo', 's', 'g')
            ->addSelect('COUNT(DISTINCT c) AS total_criteria')
            ->addSelect('COUNT(DISTINCT pc) AS program_criteria')
            ->join('lo.subject', 's')
            ->join('s.grade', 'g')
            ->leftJoin('lo.criteria', 'c')
            ->leftJoin(ActivityLearningOutcome::class, 'alo', 'WITH', 'alo.learningOutcome = lo')
            ->leftJoin('alo.activity', 'a')
            ->leftJoin('a.programGrade', 'pg')
            ->leftJoin('alo.criteria', 'pc')
            ->where('pg.trainingProgram = :trainingProgram')
            ->setParameter('trainingProgram', $trainingProgram)
            ->groupBy('lo')
            ->getQuery()
            ->getResult();

        $learningOutcomesByGrade = [];
        foreach ($learningOutcomes as $row) {
            $id = $row[0]->getSubject()->getGrade()->getId();
            if (!isset($learningOutcomesByGrade[$id])) {
                $learningOutcomesByGrade[$id] = [];
            }
            $learningOutcomesByGrade[$id][] = $row;
        }

        $result = [];
        foreach ($learningOutcomesByGrade as $gradeId => $gradeLearningOutcomes) {
            $weight = 0;
            $count = count($gradeLearningOutcomes);
            foreach ($gradeLearningOutcomes as $row) {
                $weight += $row['program_criteria'] / $row['total_criteria'];
            }
            $result[$gradeId] = ['weight' => $weight, 'count' => $count];
        }

        return $result;
    }
}
