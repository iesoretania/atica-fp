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

namespace AppBundle\Repository\WPT;

use AppBundle\Entity\WPT\Activity;
use AppBundle\Entity\WPT\AgreementEnrollment;
use AppBundle\Entity\WPT\Shift;
use AppBundle\Repository\Edu\CriterionRepository;
use AppBundle\Repository\Edu\LearningOutcomeRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ActivityRepository extends ServiceEntityRepository
{
    private $criterionRepository;
    private $learningOutcomeRepository;

    public function __construct(
        ManagerRegistry $registry,
        CriterionRepository $criterionRepository,
        LearningOutcomeRepository $learningOutcomeRepository
    ) {
        parent::__construct($registry, Activity::class);
        $this->criterionRepository = $criterionRepository;
        $this->learningOutcomeRepository = $learningOutcomeRepository;
    }

    public function findOneByCodeAndShift($code, Shift $shift)
    {
        return $this->createQueryBuilder('a')
            ->where('a.code = :code')
            ->andWhere('a.shift = :shift')
            ->setParameter('code', $code)
            ->setParameter('shift', $shift)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAllInListByIdAndShift($items, Shift $shift)
    {
        return $this->createQueryBuilder('a')
        ->where('a IN (:items)')
        ->andWhere('a.shift = :shift')
        ->setParameter('items', $items)
        ->setParameter('shift', $shift)
        ->orderBy('a.code')
        ->getQuery()
        ->getResult();
    }

    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Activity::class, 'a')
            ->where('a IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function getProgramActivitiesFromAgreementEnrollment(AgreementEnrollment $agreementEnrollment)
    {
        $activities = $this->createQueryBuilder('a')
            ->where('a IN (:items)')
            ->setParameter('items', $agreementEnrollment->getActivities())
            ->addOrderBy('a.code')
            ->getQuery()
            ->getResult();

        $result = [];

        /** @var Activity $activity */
        foreach ($activities as $activity) {
            foreach ($activity->getCriteria() as $criterion) {
                $code = $criterion->getLearningOutcome()->getCode();
                if (!isset($result[$code])) {
                    $result[$code] =
                        ['learning_outcome' => $criterion->getLearningOutcome(), 'data' => [], 'length' => 0];
                }
                if (!isset($result[$code]['data'][$activity->getCode()])) {
                    $result[$code]['data'][$activity->getCode()] = [
                        'activity' => $activity,
                        'criteria' => [],
                        'length' => 0];
                }
                $result[$code]['data'][$activity->getCode()]['criteria'][] = $criterion;
                $result[$code]['length']++;
                $result[$code]['data'][$activity->getCode()]['length']++;
            }
        }
        ksort($result);
        return $result;
    }

    public function getProgramActivitiesStatsFromAgreementEnrollmentQueryBuilder(
        AgreementEnrollment $agreementEnrollment
    ) {
        return $this->createQueryBuilder('a')
            ->addSelect('a')
            ->addSelect('COUNT(ta.hours)')
            ->addSelect('SUM(ta.hours)')
            ->join(AgreementEnrollment::class, 'ae', 'WITH', 'a MEMBER OF ae.activities')
            ->join('ae.trackedWorkDays', 'twd')
            ->leftJoin('twd.trackedActivities', 'ta', 'WITH', 'ta.activity = a')
            ->where('ae = :agreement_enrollment')
            ->setParameter('agreement_enrollment', $agreementEnrollment)
            ->groupBy('a')
            ->orderBy('a.code')
            ->addOrderBy('a.description');
    }

    public function getProgramActivitiesStatsFromAgreementEnrollment(AgreementEnrollment $agreementEnrollment)
    {
        return $this->getProgramActivitiesStatsFromAgreementEnrollmentQueryBuilder($agreementEnrollment)
            ->getQuery()
            ->getResult();
    }

    public function copyFromShift(Shift $destination, Shift $source)
    {
        $activities = $source->getActivities();

        foreach ($activities as $activity) {

            dump($activity);
            $newActivity = new Activity();
            $newActivity
                ->setShift($destination)
                ->setCode($activity->getCode())
                ->setDescription($activity->getDescription());

            $this->getEntityManager()->persist($newActivity);

            $criteria = $activity->getCriteria();

            foreach ($criteria as $criterion) {
                $learningOutcome = $this->learningOutcomeRepository
                    ->findOneByCodeAndSubject($criterion->getLearningOutcome()->getCode(), $destination->getSubject());

                if ($learningOutcome) {
                    $criterion = $this->criterionRepository
                        ->findOneByCodeAndLearningOutcome($criterion->getCode(), $learningOutcome);

                    if ($criterion && false === $newActivity->getCriteria()->contains($learningOutcome)) {
                        $newActivity->getCriteria()->add($criterion);
                        dump(['add', $criterion]);
                    }
                }
            }
        }
    }
}
