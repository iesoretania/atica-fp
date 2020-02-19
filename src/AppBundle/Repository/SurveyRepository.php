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

use AppBundle\Entity\Organization;
use AppBundle\Entity\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class SurveyRepository extends ServiceEntityRepository
{
    private $answeredSurveyRepository;

    private $answeredSurveyQuestionRepository;

    public function __construct(
        ManagerRegistry $registry,
        AnsweredSurveyRepository $answeredSurveyRepository,
        AnsweredSurveyQuestionRepository $answeredSurveyQuestionRepository
    ) {
        parent::__construct($registry, Survey::class);
        $this->answeredSurveyRepository = $answeredSurveyRepository;
        $this->answeredSurveyQuestionRepository = $answeredSurveyQuestionRepository;
    }

    public function findByOrganization(Organization $organization)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.organization = :organization')
            ->setParameter('organization', $organization)
            ->addOrderBy('s.title')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @param Organization $organization
     * @return QueryBuilder
     */
    public function findAllInListByIdAndOrganizationQueryBuilder(
        $items,
        Organization $organization
    ) {
        return $this->createQueryBuilder('s')
            ->where('s.id IN (:items)')
            ->andWhere('s.organization = :organization')
            ->setParameter('items', $items)
            ->setParameter('organization', $organization)
            ->orderBy('s.title');
    }

    /**
     * @param $items
     * @param Organization $organization
     * @return Survey[]
     */
    public function findAllInListByIdAndOrganization(
        $items,
        Organization $organization
    ) {
        return $this->findAllInListByIdAndOrganizationQueryBuilder($items, $organization)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @param Organization $organization
     * @return Survey[]
     */
    public function findAllInListByIdAndOrganizationAndNoAnswers(
        $items,
        Organization $organization
    ) {
        return $this->findAllInListByIdAndOrganizationQueryBuilder($items, $organization)
            ->andWhere('SIZE(s.answers) = 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Survey[] $list
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Survey::class, 's')
            ->where('s IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    /**
     * @param Survey[] $list
     */
    public function purgeAnswersFromList($list)
    {
        /** @var Survey $survey */
        foreach ($list as $survey) {
            $answers = $this->answeredSurveyRepository->findBySurvey($survey);
            $this->answeredSurveyQuestionRepository->pruneAnswersFromAnswerList($answers);
            $this->answeredSurveyRepository->deleteFromList($answers);
        }
    }
}
