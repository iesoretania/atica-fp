<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Repository\Edu;

use AppBundle\Entity\Edu\AcademicYear;
use AppBundle\Entity\Edu\Subject;
use AppBundle\Entity\Edu\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;

class SubjectRepository extends ServiceEntityRepository
{
    /** @var TeachingRepository */
    private $teachingRepository;

    public function __construct(ManagerRegistry $registry, TeachingRepository $teachingRepository)
    {
        parent::__construct($registry, Subject::class);

        $this->teachingRepository = $teachingRepository;
    }

    /**
     * @param AcademicYear $academicYear
     * @return QueryBuilder
     */
    public function findByAcademicYearQueryBuilder(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('s')
            ->join('s.grade', 'g')
            ->join('g.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('s.name');
    }

    /**
     * @param AcademicYear $academicYear
     * @return Subject[]
     */
    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->findByAcademicYearQueryBuilder($academicYear)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @param string $subjectInternalCode
     * @param string $gradeInternalCode
     * @return Subject|null
     */
    public function findOneByAcademicYearAndInternalCodes(AcademicYear $academicYear, $subjectInternalCode, $gradeInternalCode)
    {
        try {
            return $this->findByAcademicYearQueryBuilder($academicYear)
                ->andWhere('s.internalCode = :subject_internal_code')
                ->andWhere('g.internalCode = :grade_internal_code')
                ->setParameter('subject_internal_code', $subjectInternalCode)
                ->setParameter('grade_internal_code', $gradeInternalCode)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param Training $training
     * @param string $code
     * @return Subject|null
     */
    public function findOneByTrainingAndCode(Training $training, $code)
    {
        try {
            return $this->createQueryBuilder('s')
                ->join('s.grade', 'g')
                ->andWhere('s.code = :code')
                ->andWhere('g.training = :training')
                ->setParameter('code', $code)
                ->setParameter('training', $training)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param $items
     * @param AcademicYear $academicYear
     * @return Subject[]
     */
    public function findAllInListByIdAndAcademicYear(
        $items,
        AcademicYear $academicYear
    ) {
        return $this->createQueryBuilder('s')
            ->join('s.grade', 'g')
            ->join('g.training', 't')
            ->where('s.id IN (:items)')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('items', $items)
            ->setParameter('academic_year', $academicYear)
            ->orderBy('s.name')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param AcademicYear $academicYear
     * @param $filter
     * @return Subject[]
     */
    public function findByAcademicYearAndTrainingFilterOrdered(AcademicYear $academicYear, $filter)
    {
        return $this->createQueryBuilder('s')
            ->select('s')
            ->join('s.grade', 'g')
            ->join('g.training', 't')
            ->where('t.academicYear = :academic_year')
            ->andWhere('t.name LIKE :filter')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('filter', $filter)
            ->orderBy('t.name', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->addOrderBy('g.name', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Subject[] $list
     * @return mixed
     */
    public function deleteFromList($list)
    {
        $this->teachingRepository->deleteFromSubjectList($list);

        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Subject::class, 's')
            ->where('s IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
