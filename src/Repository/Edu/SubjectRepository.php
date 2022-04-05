<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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
use App\Entity\Edu\Group;
use App\Entity\Edu\Subject;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class SubjectRepository extends ServiceEntityRepository
{
    private $teachingRepository;
    private $learningOutcomeRepository;

    public function __construct(
        ManagerRegistry $registry,
        TeachingRepository $teachingRepository,
        LearningOutcomeRepository $learningOutcomeRepository
    ) {
        parent::__construct($registry, Subject::class);

        $this->teachingRepository = $teachingRepository;
        $this->learningOutcomeRepository = $learningOutcomeRepository;
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
    public function findOneByAcademicYearAndInternalCode(
        AcademicYear $academicYear,
        $subjectInternalCode,
        $gradeInternalCode
    ) {
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

    public function findByGroupAndPerson(Group $group, Person $person = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->select('s')
            ->join('s.grade', 'gr')
            ->where(':group MEMBER OF gr.groups')
            ->setParameter('group', $group)
            ->orderBy('s.name', 'ASC');

        if ($person !== null) {
            $qb
                ->join('s.teachings', 't')
                ->join('t.teacher', 'te')
                ->andWhere('te.person = :person')
                ->setParameter('person', $person);
        }

        return $qb
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

    /**
     * @param Grade $grade
     * @return Subject[]|Collection
     */
    public function findByGrade(Grade $grade)
    {
        return $this->createQueryBuilder('s')
            ->where('s.grade = :grade')
            ->setParameter('grade', $grade)
            ->getQuery()
            ->getResult();
    }

    public function copyFromGrade(Grade $destination, Grade $source)
    {
        $subjects = $this->findByGrade($source);
        foreach ($subjects as $subject) {
            $newSubject = new Subject();
            $newSubject
                ->setGrade($destination)
                ->setName($subject->getName())
                ->setInternalCode($subject->getInternalCode())
                ->setCode($subject->getCode());

            $this->getEntityManager()->persist($newSubject);

            $this->learningOutcomeRepository->copyFromSubject($newSubject, $subject);
        }
    }

    public function findOneByGradeAndName(Grade $grade, string $subjectName)
    {

        return $this->createQueryBuilder('s')
            ->where('s.grade = :grade')
            ->andWhere('s.name = :subject')
            ->setParameter('grade', $grade)
            ->setParameter('subject', $subjectName)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
