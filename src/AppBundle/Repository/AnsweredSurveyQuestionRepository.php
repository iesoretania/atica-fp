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

namespace AppBundle\Repository;

use AppBundle\Entity\AnsweredSurveyQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class AnsweredSurveyQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnsweredSurveyQuestion::class);
    }

    public function pruneAnswersFromAnswerList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(AnsweredSurveyQuestion::class, 'asq')
            ->where('asq.answeredSurvey IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function notNumericAnswersBySurveyAndAnsweredSurveyList($list)
    {
        return $this->createQueryBuilder('asq')
            ->select('asq')
            ->addSelect('sq')
            ->leftJoin('asq.surveyQuestion', 'sq')
            ->leftJoin('asq.answeredSurvey', 'asu')
            ->where('asq.answeredSurvey IN (:list)')
            ->andWhere('asq.textValue IS NOT NULL')
            ->andWhere('asq.textValue != \'\'')
            ->setParameter('list', $list)
            ->orderBy('sq.orderNr')
            ->getQuery()
            ->getResult();
    }
}
