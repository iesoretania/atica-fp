<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Entity\ItpModule\Activity;
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
        $learningOutcomes = $this->getEntityManager()->createQueryBuilder()
            ->select('s, g, lo AS learning_outcome, COUNT(DISTINCT c) AS total_criteria, COUNT(DISTINCT ac) AS selected_criteria, COUNT(DISTINCT ac) / COUNT(DISTINCT c) AS weight')
            ->from(LearningOutcome::class, 'lo')
            ->join('lo.subject', 's')
            ->join('lo.criteria', 'c')
            ->join('s.grade', 'g')
            ->join(ProgramGrade::class, 'pg', 'WITH', 'pg.grade = g')
            ->leftJoin(Activity::class, 'a', 'WITH', 'c MEMBER OF a.criteria AND a.programGrade = pg')
            ->leftJoin(Criterion::class, 'ac', 'WITH', 'ac = c AND ac MEMBER OF a.criteria')
            ->where('pg.trainingProgram = :trainingProgram')
            ->setParameter('trainingProgram', $trainingProgram)
            ->orderBy('g.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->addOrderBy('lo.code', 'ASC')
            ->groupBy('lo')
            ->getQuery()
            ->getResult();
        $grades = [];
        $lastProgramGradeId = null;
        $lastSubjectId = null;
        $totalSubjectCount = 0;
        $selectedSubjectCount = 0;
        $totalLearningOutcomesCount = 0;
        $selectedLearningOutcomesCount = 0;
        $internalLearningOutcomesCount = 0;
        $totalCriteriaCount = 0;
        $selectedCriteriaCount = 0;
        $weightSum = 0;
        $subjectWeightSum = 0;
        $isSubjectSelected = false;
        foreach ($learningOutcomes as $learningOutcomeData) {
            $learningOutcome = $learningOutcomeData['learning_outcome'];
            $subject = $learningOutcome->getSubject();
            if ($lastProgramGradeId !== $subject->getGrade()->getId()) {
                $subjectWeightSum += $internalLearningOutcomesCount !== 0 ? $weightSum / $internalLearningOutcomesCount : 0;
                if ($lastProgramGradeId !== null) {
                    $grades[$lastProgramGradeId] = [
                        'grade' => $subject->getGrade(),
                        'total_subjects' => $totalSubjectCount,
                        'total_learning_outcomes' => $totalLearningOutcomesCount,
                        'total_criteria' => $totalCriteriaCount,
                        'selected_subjects' => $selectedSubjectCount,
                        'selected_learning_outcomes' => $selectedLearningOutcomesCount,
                        'selected_criteria' => $selectedCriteriaCount,
                        'weight' => $totalSubjectCount != 0 ? $subjectWeightSum / $totalSubjectCount : 0
                    ];
                }
                $lastProgramGradeId = $subject->getGrade()->getId();
                $totalSubjectCount = 0;
                $selectedSubjectCount = 0;
                $totalLearningOutcomesCount = 0;
                $selectedLearningOutcomesCount = 0;
                $internalLearningOutcomesCount = 0;
                $totalCriteriaCount = 0;
                $selectedCriteriaCount = 0;
                $weightSum = 0;
                $subjectWeightSum = 0;
                $isSubjectSelected = false;
            }
            if ($lastSubjectId !== $subject->getId()) {
                $lastSubjectId = $subject->getId();
                $subjectWeightSum += $internalLearningOutcomesCount != 0 ? $weightSum / $internalLearningOutcomesCount : 0;
                $internalLearningOutcomesCount = 0;
                $weightSum = 0;
                $totalSubjectCount++;
                $isSubjectSelected = false;
            }
            $totalCriteriaCount += $learningOutcomeData['total_criteria'];
            $totalLearningOutcomesCount++;
            $internalLearningOutcomesCount++;
            if ($learningOutcomeData['selected_criteria'] > 0) {
                $selectedLearningOutcomesCount++;
                if (!$isSubjectSelected) {
                    $selectedSubjectCount++;
                    $isSubjectSelected = true;
                }
                $selectedCriteriaCount += $learningOutcomeData['selected_criteria'];
                $weightSum += $learningOutcomeData['weight'];
            }
        }
        if ($totalSubjectCount > 0 && !isset($grades[$subject->getGrade()->getId()])) {
            $subjectWeightSum += $internalLearningOutcomesCount != 0 ? $weightSum / $internalLearningOutcomesCount : 0;
            $grades[$subject->getGrade()->getId()] = [
                'grade' => $subject->getGrade(),
                'total_subjects' => $totalSubjectCount,
                'total_learning_outcomes' => $totalLearningOutcomesCount,
                'total_criteria' => $totalCriteriaCount,
                'selected_subjects' => $selectedSubjectCount,
                'selected_learning_outcomes' => $selectedLearningOutcomesCount,
                'selected_criteria' => $selectedCriteriaCount,
                'weight' => $totalSubjectCount != 0 ? $subjectWeightSum / $totalSubjectCount : 0
            ];
        }

        foreach ($trainingProgram->getTrainingProgramGrades() as $trainingProgramGrade) {
            $grade = $trainingProgramGrade->getGrade();
            if (isset($grades[$grade->getId()])) {
                $grades[$grade->getId()]['program_grade'] = $trainingProgramGrade;
            }
        }
        return $grades;
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

    public function countActivities(ProgramGrade $getProgramGrade): int
    {
        return $this->createQueryBuilder('pg')
            ->select('SIZE(pg.activities)')
            ->where('pg = :program_grade')
            ->setParameter('program_grade', $getProgramGrade)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteFromGradeListAndTrainingProgram(array $deletedGrades, TrainingProgram $trainingProgram): void
    {
        $programGrades = $this->createQueryBuilder('pg')
            ->where('pg.trainingProgram = :trainingProgram')
            ->andWhere('pg.grade IN (:grades)')
            ->setParameter('trainingProgram', $trainingProgram)
            ->setParameter('grades', $deletedGrades)
            ->getQuery()
            ->getResult();

        $this->deleteFromList($programGrades);
    }
}
