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

use AppBundle\Entity\AnsweredSurveyQuestion;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Survey;
use AppBundle\Entity\SurveyQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class SurveyQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SurveyQuestion::class);
    }

    public function getLastOrderNr(Survey $survey)
    {
        return $this->createQueryBuilder('sq')
            ->select('MAX(sq.orderNr)')
            ->where('sq.survey = :survey')
            ->setParameter('survey', $survey)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param SurveyQuestion $surveyQuestion
     * @return SurveyQuestion|null
     */
    public function getPreviousQuestion(SurveyQuestion $surveyQuestion)
    {
        return $this->createQueryBuilder('sq')
            ->where('sq.orderNr < :order_nr')
            ->andWhere('sq.survey = :survey')
            ->setParameter('order_nr', $surveyQuestion->getOrderNr())
            ->setParameter('survey', $surveyQuestion->getSurvey())
            ->setMaxResults(1)
            ->orderBy('sq.orderNr', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param SurveyQuestion $surveyQuestion
     * @return SurveyQuestion|null
     */
    public function getNextQuestion(SurveyQuestion $surveyQuestion)
    {
        return $this->createQueryBuilder('sq')
            ->where('sq.orderNr > :order_nr')
            ->andWhere('sq.survey = :survey')
            ->setParameter('order_nr', $surveyQuestion->getOrderNr())
            ->setParameter('survey', $surveyQuestion->getSurvey())
            ->setMaxResults(1)
            ->orderBy('sq.orderNr', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param $items
     * @param Organization $organization
     * @return SurveyQuestion[]
     */
    public function findAllInListByIdAndOrganization(
        $items,
        Organization $organization
    ) {
        return $this->createQueryBuilder('sq')
            ->join('sq.survey', 's')
            ->where('sq.id IN (:items)')
            ->andWhere('s.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('sq.orderNr')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param SurveyQuestion[] $list
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(SurveyQuestion::class, 'sq')
            ->where('sq IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function answerStatsBySurveyAndAnsweredSurveyList(Survey $survey, $list)
    {
        return $this->createQueryBuilder('sq')
            ->select('sq')
            ->addSelect('COUNT(asq.numericValue)')
            ->addSelect('AVG(asq.numericValue)')
            ->addSelect('MIN(asq.numericValue)')
            ->addSelect('MAX(asq.numericValue)')
            ->leftJoin(AnsweredSurveyQuestion::class, 'asq', 'WITH', 'asq.surveyQuestion = sq')
            ->leftJoin('asq.answeredSurvey', 'asu')
            ->groupBy('sq')
            ->where('asu IN (:list)')
            ->andWhere('sq.survey = :survey')
            ->setParameter('list', $list)
            ->setParameter('survey', $survey)
            ->orderBy('sq.orderNr')
            ->getQuery()
            ->getResult();
    }
}
