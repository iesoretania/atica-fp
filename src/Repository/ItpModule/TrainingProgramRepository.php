<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingProgram>
 */
class TrainingProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly ProgramGradeRepository $programGradeRepository)
    {
        parent::__construct($registry, TrainingProgram::class);
    }

    public function findByAcademicYear(AcademicYear $academicYear): array
    {
        return $this->createQueryBuilder('tp')
            ->addSelect('tr')
            ->join('tp.training', 'tr')
            ->where('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('tr.name')
            ->getQuery()
            ->getResult();
    }

    public function findAllInListByIdAndAcademicYear(array $items, AcademicYear $academicYear): array
    {
        return $this->createQueryBuilder('tp')
            ->addSelect('tpg', 'gr', 'tr')
            ->where('tp IN (:items)')
            ->join('tp.trainingProgramGrades', 'tpg')
            ->join('tpg.grade', 'gr')
            ->join('gr.training', 'tr')
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('tr.name')
            ->getQuery()
            ->getResult();
    }

    public function createProgramRepositoryQueryBuilder(?AcademicYear $academicYear, bool $isManager, Person $person, ?Teacher $teacher, $q): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('tp')
            ->addSelect('pg', 'pgg', 'gr', 'tr')
            ->distinct()
            ->join('tp.trainingProgramGrades', 'pg')
            ->join('pg.trainingProgramGroups', 'pgg')
            ->join('pg.grade', 'gr')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->orderBy('tp.name');

        if ($q) {
            $queryBuilder
                ->where('tp.name LIKE :tq OR gr.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        if ($teacher instanceof Teacher && !$isManager) {
            $queryBuilder
                ->andWhere('(d.head IS NOT NULL AND d.head = :teacher) OR :teacher MEMBER OF pgg.managers')
                ->setParameter('teacher', $teacher);
        }

        $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        return $queryBuilder;
    }

    public function deleteFromList($items)
    {
        $this->programGradeRepository->deleteFromTrainingProgramList($items);
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(TrainingProgram::class, 'tp')
            ->where('tp IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function persist(TrainingProgram $trainingProgram): void
    {
        $this->getEntityManager()->persist($trainingProgram);
    }

    public function findByAcademicYearAndWorkcenter(AcademicYear $academicYear, Workcenter $workcenter): array
    {
        return $this->createQueryBuilder('tp')
            ->distinct()
            ->addSelect('pg', 'pgg', 'gr', 'tr')
            ->join('tp.trainingProgramGrades', 'pg')
            ->join('pg.trainingProgramGroups', 'pgg')
            ->join('pg.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('pgg.studentPrograms', 'sp')
            ->join('sp.studentProgramWorkcenters', 'spw')
            ->join('spw.workcenter', 'w')
            ->where('tr.academicYear = :academic_year')
            ->andWhere('w = :workcenter')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('workcenter', $workcenter)
            ->orderBy('tp.name')
            ->getQuery()
            ->getResult();
    }

    public function createByManagerQueryBuilder(?AcademicYear $academicYear, bool $isManager, Person $person, ?string $q): QueryBuilder
    {
        $queryBuilder = $this->createProgramRepositoryQueryBuilder($academicYear, $isManager, $person, null, $q);

        if (!$isManager) {
            $queryBuilder
                ->join('pgg.managers', 'm')
                ->andWhere('m = :person')
                ->setParameter('person', $person);
        }

        return $queryBuilder;
    }
}
