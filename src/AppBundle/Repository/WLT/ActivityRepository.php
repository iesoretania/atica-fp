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

namespace AppBundle\Repository\WLT;

use AppBundle\Entity\Edu\Training;
use AppBundle\Entity\WLT\Activity;
use AppBundle\Entity\WLT\ActivityRealization;
use AppBundle\Entity\WLT\Project;
use AppBundle\Repository\Edu\CompetencyRepository;
use AppBundle\Repository\Edu\LearningOutcomeRepository;
use AppBundle\Repository\Edu\SubjectRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

class ActivityRepository extends ServiceEntityRepository
{
    private $competencyRepository;
    private $activityRealizationRepository;
    private $learningOutcomeRepository;
    private $subjectRepository;

    public function __construct(
        ManagerRegistry $registry,
        CompetencyRepository $competencyRepository,
        ActivityRealizationRepository $activityRealizationRepository,
        LearningOutcomeRepository $learningOutcomeRepository,
        SubjectRepository $subjectRepository
    ) {
        parent::__construct($registry, Activity::class);
        $this->competencyRepository = $competencyRepository;
        $this->activityRealizationRepository = $activityRealizationRepository;
        $this->learningOutcomeRepository = $learningOutcomeRepository;
        $this->subjectRepository = $subjectRepository;
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
        } catch (NonUniqueResultException $e) {
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
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Activity::class, 'a')
            ->where('a IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function copyFromProject(Project $destination, Project $source)
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

                if ($newTraining) {
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
                    foreach ($trainings as $training) {
                        $subject = $this->subjectRepository->findOneByAcademicYearAndInternalCode(
                            $training->getAcademicYear(),
                            $learningOutcome->getSubject()->getInternalCode(),
                            $learningOutcome->getSubject()->getGrade()->getInternalCode()
                        );

                        if (null !== $subject) {
                            $newSubject = $subject;
                            break;
                        }
                    }

                    if ($newSubject) {
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
