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

namespace AppBundle\Repository\WLT;

use AppBundle\Entity\Company;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Subject;
use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\WLT\Activity;
use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Entity\WLT\Agreement;
use AppBundle\Entity\WLT\AgreementActivityRealization;
use AppBundle\Entity\WLT\LearningProgram;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

class ActivityRealizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityRealization::class);
    }

    public function findAllInListByIdAndActivity(
        $items,
        Activity $activity
    ) {
        return $this->createQueryBuilder('ar')
            ->where('ar IN (:items)')
            ->andWhere('ar.activity = :activity')
            ->setParameter('items', $items)
            ->setParameter('activity', $activity)
            ->orderBy('ar.code')
            ->getQuery()
            ->getResult();
    }

    public function findByTraining(Training $training)
    {
        return $this->createQueryBuilder('ar')
            ->join('ar.activity', 'a')
            ->join('a.subject', 's')
            ->join('s.grade', 'g')
            ->andWhere('g.training = :training')
            ->setParameter('training', $training)
            ->orderBy('s.code')
            ->addOrderBy('s.name')
            ->addOrderBy('a.code')
            ->addOrderBy('ar.code')
            ->getQuery()
            ->getResult();
    }

    public function findByTrainingAndCompany(Training $training, Company $company)
    {
        return $this->createQueryBuilder('ar')
            ->join('ar.activity', 'a')
            ->join('a.subject', 's')
            ->join('s.grade', 'g')
            ->join(LearningProgram::class, 'lp', 'WITH', 'ar MEMBER OF lp.activityRealizations')
            ->andWhere('g.training = :training')
            ->andWhere('lp.company = :company')
            ->setParameter('training', $training)
            ->setParameter('company', $company)
            ->orderBy('s.code')
            ->addOrderBy('s.name')
            ->addOrderBy('a.code')
            ->addOrderBy('ar.code')
            ->getQuery()
            ->getResult();
    }

    public function findOneByTrainingAndCode(Training $training, $code)
    {
        try {
            return $this->createQueryBuilder('ar')
                ->join('ar.activity', 'a')
                ->join('a.subject', 's')
                ->join('s.grade', 'g')
                ->andWhere('g.training = :training')
                ->andWhere('ar.code = :code')
                ->setParameter('training', $training)
                ->setParameter('code', $code)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    public function deleteFromList($items)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(ActivityRealization::class, 'ar')
            ->where('ar IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function findLockedByAgreement(Agreement $agreement)
    {
        return $this->createQueryBuilder('ar')
            ->join(
                AgreementActivityRealization::class,
                'aar',
                'WITH',
                'aar.activityRealization = ar'
            )
            ->where('aar.agreement = :agreement')
            ->andWhere('aar.grade IS NOT NULL')
            ->setParameter('agreement', $agreement)
            ->getQuery()
            ->getResult();
    }

    public function reportByStudentEnrollmentAndSubject(StudentEnrollment $studentEnrollment, Subject $subject)
    {
        return $this->createQueryBuilder('ar')
            ->addSelect('AVG(gr.numericGrade)')
            ->addSelect('COUNT(aar)')
            ->join(AgreementActivityRealization::class, 'aar', 'WITH', 'aar.activityRealization = ar')
            ->leftJoin('ar.learningOutcomes', 'l')
            ->join('l.subject', 's')
            ->join('aar.agreement', 'a')
            ->leftJoin('aar.grade', 'gr')
            ->leftJoin('aar.gradedBy', 'p')
            ->andWhere('a.studentEnrollment = :student_enrollment')
            ->andWhere('l.subject = :subject')
            ->setParameter('student_enrollment', $studentEnrollment)
            ->setParameter('subject', $subject)
            ->orderBy('s.name')
            ->addOrderBy('ar.code')
            ->addGroupBy('ar')
            ->addGroupBy('s')
            ->getQuery()
            ->getResult();
    }
}
