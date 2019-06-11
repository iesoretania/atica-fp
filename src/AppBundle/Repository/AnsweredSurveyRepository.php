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

namespace AppBundle\Repository;

use AppBundle\Entity\AnsweredSurvey;
use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\Survey;
use AppBundle\Entity\WLT\Agreement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class AnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnsweredSurvey::class);
    }

    public function findBySurvey(Survey $survey)
    {
        return $this->findBy(['survey'=> $survey]);
    }

    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(AnsweredSurvey::class, 'ans')
            ->where('ans IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function findByWltStudentSurveyAndTraining(Survey $survey, Training $training)
    {
        return $this->createQueryBuilder('asu')
            ->join('asu.survey', 's')
            ->innerJoin(Agreement::class, 'a', 'WITH', 'a.studentSurvey = asu')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'gro')
            ->join('gro.grade', 'gra')
            ->where('s = :survey')
            ->andWhere('gra.training = :training')
            ->setParameter('survey', $survey)
            ->setParameter('training', $training)
            ->getQuery()
            ->getResult();
    }
    public function findByWltCompanySurveyAndTraining(Survey $survey, Training $training)
    {
        return $this->createQueryBuilder('asu')
            ->join('asu.survey', 's')
            ->innerJoin(Agreement::class, 'a', 'WITH', 'a.companySurvey = asu')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'gro')
            ->join('gro.grade', 'gra')
            ->where('s = :survey')
            ->andWhere('gra.training = :training')
            ->setParameter('survey', $survey)
            ->setParameter('training', $training)
            ->getQuery()
            ->getResult();
    }
}
