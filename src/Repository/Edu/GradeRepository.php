<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace App\Repository\Edu;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Grade;
use App\Entity\Edu\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class GradeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly SubjectRepository $subjectRepository)
    {
        parent::__construct($registry, Grade::class);
    }

    /**
     * @return QueryBuilder
     */
    public function findByAcademicYearQueryBuilder(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('t.name')
            ->addOrderBy('g.name');
    }

    /**
     * @return Grade[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $internalCode
     * @return Grade|null
     */
    public function findOneByAcademicYearAndInternalCode(AcademicYear $academicYear, $internalCode)
    {
        try {
            return $this->findByAcademicYearQueryBuilder($academicYear)
                ->andWhere('g.internalCode = :internal_code')
                ->setParameter('internal_code', $internalCode)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        }
        catch(NonUniqueResultException) {
            return null;
        }
    }

    /**
     * @param $items
     * @return Grade[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('g')
            ->join('g.training', 't')
            ->where('g.id IN (:items)')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('t.name')
            ->addOrderBy('g.name')
            ->getQuery()
            ->getResult();
    }


    /**
     * @return Grade[]|Collection
     */
    public function findByTraining(Training $training)
    {
        return $this->createQueryBuilder('g')
            ->where('g.training = :training')
            ->setParameter('training', $training)
            ->getQuery()
            ->getResult();
    }

    public function copyFromTraining(
        Training $destination,
        Training $source
    ): void {
        $grades = $this->findByTraining($source);
        foreach ($grades as $grade) {
            $newGrade = new Grade();
            $newGrade
                ->setTraining($destination)
                ->setName($grade->getName())
                ->setInternalCode($grade->getInternalCode());
            $this->getEntityManager()->persist($newGrade);

            $this->subjectRepository->copyFromGrade($newGrade, $grade);
        }
    }

    public function deleteFromList(array $grades)
    {
        $this->subjectRepository->deleteFromGradesList($grades);

        return $this->getEntityManager()
            ->createQueryBuilder()
            ->delete(Grade::class, 'g')
            ->where('g IN (:items)')
            ->setParameter('items', $grades)
            ->getQuery()
            ->execute();
    }
}
