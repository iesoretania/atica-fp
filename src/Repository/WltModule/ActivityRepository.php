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

namespace App\Repository\WltModule;

use App\Entity\Edu\Training;
use App\Entity\WltModule\Activity;
use App\Entity\WltModule\ActivityRealization;
use App\Entity\WltModule\Project;
use App\Repository\Edu\CompetencyRepository;
use App\Repository\Edu\LearningOutcomeRepository;
use App\Repository\Edu\SubjectRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class ActivityRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly CompetencyRepository $competencyRepository,
        private readonly ActivityRealizationRepository $activityRealizationRepository,
        private readonly LearningOutcomeRepository $learningOutcomeRepository,
        private readonly SubjectRepository $subjectRepository
    ) {
        parent::__construct($registry, Activity::class);
    }

    public function findOneByProjectAndCode(Project $project, $code)
    {
        try {
            return $this->createQueryBuilder('a')
                ->where('a.project = :project')
                ->andWhere('a.code = :code')
                ->setParameter('project', $project)
                ->setParameter('code', $code)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            return null;
        }
    }

    public function findAllInListByIdAndProject(
        $items,
        Project $project
    ) {
        return $this->createQueryBuilder('a')
            ->where('a IN (:items)')
            ->andWhere('a.project = :project')
            ->setParameter('items', $items)
            ->setParameter('project', $project)
            ->orderBy('a.code')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($items)
    {
        $this->getEntityManager()->createQueryBuilder()
            ->delete(ActivityRealization::class, 'ar')
            ->where('ar.activity IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Activity::class, 'a')
            ->where('a IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function copyFromProject(Project $destination, Project $source): void
    {
        $trainings = [];
        foreach ($destination->getGroups() as $group) {
            if (!in_array($group->getGrade()->getTraining(), $trainings, false)) {
                $trainings[] = $group->getGrade()->getTraining();
            }
        }

        $activities = $source->getActivities();

        foreach ($activities as $activity) {
            $newActivity = $this->findOneByProjectAndCode($destination, $activity->getCode());

            if (null === $newActivity) {
                $newActivity = new Activity();
                $this->getEntityManager()->persist($newActivity);
            }

            $newActivity
                ->setProject($destination)
                ->setCode($activity->getCode())
                ->setPriorLearning($activity->getPriorLearning())
                ->setDescription($activity->getDescription());

            // añadir competencias
            $competencies = $activity->getCompetencies();

            foreach ($competencies as $competency) {
                // comprobamos primero que existe la enseñanza, si no existe no hacemos nada
                $newTraining = null;

                /** @var Training $training */
                foreach ($trainings as $training) {
                    if ($training->getInternalCode() === $competency->getTraining()->getInternalCode()) {
                        $newTraining = $training;
                        break;
                    }
                }

                if ($newTraining !== null) {
                    $competency = $this->competencyRepository
                        ->findOneByCodeAndTraining($competency->getCode(), $newTraining);

                    if ($competency && false === $newActivity->getCompetencies()->contains($competency)) {
                        $newActivity->getCompetencies()->add($competency);
                    }
                }
            }

            // copiar concreciones de actividades
            $activityRealizations = $activity->getActivityRealizations();

            foreach ($activityRealizations as $activityRealization) {
                $newActivityRealization = $this
                    ->activityRealizationRepository
                    ->findOneByProjectAndCode($destination, $activityRealization->getCode());

                if (null === $newActivityRealization) {
                    $newActivityRealization = new ActivityRealization();
                    $this->getEntityManager()->persist($newActivityRealization);
                }

                $newActivityRealization
                    ->setCode($activityRealization->getCode())
                    ->setDescription($activityRealization->getDescription())
                    ->setActivity($newActivity);

                // incluir resultados de aprendizaje de la concreción de actividad
                foreach ($activityRealization->getLearningOutcomes() as $learningOutcome) {
                    $newSubject = null;
                    foreach ($destination->getGroups() as $group) {
                        $subject = $this->subjectRepository->findOneByGradeAndName(
                            $group->getGrade(),
                            $learningOutcome->getSubject()->getName()
                        );

                        if (null !== $subject) {
                            $newSubject = $subject;
                            break;
                        }
                    }

                    if ($newSubject !== null) {
                        $newLearningOutcome = $this->learningOutcomeRepository->findOneByCodeAndSubject(
                            $learningOutcome->getCode(),
                            $newSubject
                        );

                        if (null !== $newLearningOutcome
                            && false === $newActivityRealization
                                ->getLearningOutcomes()->contains($newLearningOutcome)
                        ) {
                            $newActivityRealization->getLearningOutcomes()->add($newLearningOutcome);
                        }
                    }
                }
            }
        }
    }
}
