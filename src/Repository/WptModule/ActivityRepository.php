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

namespace App\Repository\WptModule;

use App\Entity\Edu\LearningOutcome;
use App\Entity\WltModule\Project;
use App\Entity\WptModule\Activity;
use App\Entity\WptModule\AgreementEnrollment;
use App\Entity\WptModule\Shift;
use App\Repository\Edu\CriterionRepository;
use App\Repository\Edu\LearningOutcomeRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CriterionRepository $criterionRepository,
        private readonly LearningOutcomeRepository $learningOutcomeRepository
    ) {
        parent::__construct($registry, Activity::class);
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

    /**
     * @return array<mixed, array<'data'|'learning_outcome'|'length', mixed>>
     */
    public function getProgramActivitiesFromAgreementEnrollment(AgreementEnrollment $agreementEnrollment): array
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

    public function copyFromShift(Shift $destination, Shift $source): void
    {
        $activities = $source->getActivities();

        foreach ($activities as $activity) {
            $newActivity = $this->findOneByCodeAndShift($activity->getCode(), $destination);

            if (!$newActivity instanceof Activity) {
                $newActivity = new Activity();
                $this->getEntityManager()->persist($newActivity);
            }

            $newActivity
                ->setShift($destination)
                ->setCode($activity->getCode())
                ->setDescription($activity->getDescription());

            $criteria = $activity->getCriteria();

            foreach ($criteria as $criterion) {
                $subject = $source->getSubject();
                $learningOutcome = $this->learningOutcomeRepository
                    ->findOneByCodeAndSubject($criterion->getLearningOutcome()->getCode(), $subject);
                if ($learningOutcome instanceof LearningOutcome) {
                    $criterion = $this->criterionRepository
                        ->findOneByCodeAndLearningOutcome($criterion->getCode(), $learningOutcome);
                    if ($criterion && !$newActivity->getCriteria()->contains($learningOutcome)) {
                        $newActivity->getCriteria()->add($criterion);
                    }
                }
            }
        }
    }

    public function copyFromWLTProject(Shift $destination, Project $source): void
    {
        $activities = $source->getActivities();

        $count = 1;

        foreach ($activities as $wltActivity) {
            foreach ($wltActivity->getActivityRealizations() as $activityRealization) {
                $code = $activityRealization->getCode();
                if (empty($code)) {
                    $code = 'A' . $count;
                    $count++;
                }
                $activity = $this->findOneByCodeAndShift($code, $destination);

                if ($activity === null) {
                    $activity = new Activity();
                    $activity
                        ->setShift($destination)
                        ->setCode($code);
                    $this->getEntityManager()->persist($activity);
                }
                $activity->setDescription($activityRealization->getDescription());
            }
        }
    }

    public function deleteFromShifts($items): void
    {
        /** @var Shift $shift */
        foreach ($items as $shift) {
            $this->deleteFromList($shift->getActivities());
        }
    }
}
