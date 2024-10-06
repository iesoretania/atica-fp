<?php

namespace App\Repository\ItpModule;

use App\Entity\ItpModule\CompanyProgram;
use App\Entity\ItpModule\ProgramGrade;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CompanyProgram>
 */
class CompanyProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CompanyProgram::class);
    }

    public function createByProgramGradeQueryBuilder(ProgramGrade $programGrade, ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('cp')
            ->join('cp.company', 'c')
            ->addOrderBy('c.code', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->where('cp.programGrade = :programGrade')
            ->setParameter('programGrade', $programGrade);

        if ($q) {
            $qb->andWhere('c.name LIKE :tq')
                ->setParameter('tq', '%' . $q . '%');
        }

        return $qb;
    }

    public function getCompanyProgramStats(ProgramGrade $programGrade): array
    {
        $result = $this->createQueryBuilder('cp')
            ->select('cp AS company_program')
            ->addSelect('c')
            ->addSelect('COUNT(DISTINCT pa) AS activity_count')
            ->addSelect('COUNT(DISTINCT cr) AS criteria_count')
            ->addSelect('COUNT(DISTINCT lo) AS learning_outcome_count')
            ->join('cp.company', 'c')
            ->leftJoin('cp.programActivities', 'pa')
            ->leftJoin('pa.criteria', 'cr')
            ->leftJoin('cr.learningOutcome', 'lo')
            ->addOrderBy('c.code', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->where('cp.programGrade = :programGrade')
            ->setParameter('programGrade', $programGrade)
            ->groupBy('cp')
            ->getQuery()
            ->getResult();

        $return = [];
        foreach ($result as $item) {
            $return[$item['company_program']->getId()] = $item;
        }

        return $return;
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function persist(CompanyProgram $companyProgram)
    {
        $this->getEntityManager()->persist($companyProgram);
    }

    public function findAllInListByIdAndProgramGrade(array $items, ProgramGrade $programGrade): array
    {
        return $this->createQueryBuilder('cp')
            ->where('cp IN (:items)')
            ->join('cp.company', 'c')
            ->andWhere('cp.programGrade = :programGrade')
            ->setParameter('items', $items)
            ->setParameter('programGrade', $programGrade)
            ->orderBy('c.name', 'ASC')
            ->addOrderBy('c.code', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList(array $items): void
    {
        $this->createQueryBuilder('cp')
            ->delete()
            ->where('cp IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function deleteFromProgramGradeList(array $items): void
    {
        $this->createQueryBuilder('cp')
            ->delete()
            ->where('cp.programGrade IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
