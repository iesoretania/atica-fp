<?php

namespace App\Repository\ItpModule;

use App\Entity\Company;
use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\CompanyProgram;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activity>
 */
class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CompanyProgramRepository $companyProgramRepository,
        private readonly StudentProgramWorkcenterActivityRepository $studentProgramWorkcenterActivityRepository
    )
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

    public function deleteFromProgramGradeList($items)
    {
        $activities = $this->createQueryBuilder('a')
            ->where('a.programGrade IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
        $this->deleteFromList($activities);
    }

    public function findAllInListByIdAndProgramGrade(array $items, ProgramGrade $programGrade): array
    {
        return $this->createQueryBuilder('a')
            ->where('a IN (:items)')
            ->andWhere('a.programGrade = :programGrade')
            ->setParameter('items', $items)
            ->setParameter('programGrade', $programGrade)
            ->orderBy('a.code', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList(array $items): void
    {
        $this->studentProgramWorkcenterActivityRepository->deleteFromActivityList($items);
        $this->createQueryBuilder('a')
            ->delete()
            ->where('a IN (:selectedItems)')
            ->setParameter('selectedItems', $items)
            ->getQuery()
            ->execute();
    }

    final public function findByProgramGradeAndCompany(ProgramGrade $programGrade, Company $company): array
    {
        $companyProgram = $this->companyProgramRepository->findOneByProgramGradeAndCompany($programGrade, $company);
        if (!$companyProgram instanceof CompanyProgram) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->where('a IN (:activities)')
            ->setParameter('activities', $companyProgram->getProgramActivities())
            ->orderBy('a.code', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    final public function findDisabledByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createQueryBuilder('a')
            ->distinct()
            ->join(StudentProgramWorkcenterActivity::class, 'spwa', 'WITH', 'spwa.activity = a')
            ->where('spwa.studentProgramWorkcenter = :studentProgramWorkcenter')
            ->andWhere('spwa.disabled = true')
            ->setParameter('studentProgramWorkcenter', $studentProgramWorkcenter)
            ->addOrderBy('a.code', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    final public function findByStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        return $this->createQueryBuilder('a')
            ->distinct()
            ->join(StudentProgramWorkcenterActivity::class, 'spwa', 'WITH', 'spwa.activity = a')
            ->where('spwa.studentProgramWorkcenter = :studentProgramWorkcenter')
            ->setParameter('studentProgramWorkcenter', $studentProgramWorkcenter)
            ->addOrderBy('a.code', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
