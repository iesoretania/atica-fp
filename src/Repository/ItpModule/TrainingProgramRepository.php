<?php

namespace App\Repository\ItpModule;

use App\Entity\Edu\AcademicYear;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingProgram>
 */
class TrainingProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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
            ->addSelect('tr')
            ->where('tp IN (:items)')
            ->join('tp.training', 'tr')
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('tr.name')
            ->getQuery()
            ->getResult();
    }

    public function createProgramRepositoryQueryBuilder(?AcademicYear $academicYear, bool $isManager, Person $person, $q): QueryBuilder
    {
        $queryBuilder = $this->createQueryBuilder('tp')
            ->addSelect('tr')
            ->distinct()
            ->join('tp.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'h')
            ->orderBy('tr.name');

        if ($q) {
            $queryBuilder
                ->where('tr.name LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        if (!$isManager) {
            $queryBuilder
                ->andWhere('d.head IS NOT NULL AND h.person = :manager')
                ->setParameter('manager', $person);
        }

        $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        return $queryBuilder;
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(TrainingProgram::class, 'tp')
            ->where('tp IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }
}
