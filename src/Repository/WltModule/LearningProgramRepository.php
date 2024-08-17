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

use App\Entity\Company;
use App\Entity\WltModule\ActivityRealization;
use App\Entity\WltModule\LearningProgram;
use App\Entity\WltModule\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LearningProgramRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly ActivityRealizationRepository $activityRealizationRepository)
    {
        parent::__construct($registry, LearningProgram::class);
    }

    private function findByProjectQueryBuilder(Project $project)
    {
        return $this->createQueryBuilder('lp')
            ->join('lp.company', 'c')
            ->where('lp.project = :project')
            ->setParameter('project', $project)
            ->orderBy('c.name');
    }

    public function findByProject(Project $project)
    {
        return $this->findByProjectQueryBuilder($project)
            ->getQuery()
            ->getResult();
    }

    public function findAllInListByIdAndProject(
        $items,
        Project $project
    ) {
        return $this->findByProjectQueryBuilder($project)
            ->andWhere('lp.id IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param LearningProgram[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(LearningProgram::class, 'lp')
            ->where('lp IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function copyFromProject(Project $destination, Project $source): void
    {
        $learningPrograms = $this->findByProject($source);

        /** @var LearningProgram $learningProgram */
        foreach ($learningPrograms as $learningProgram) {
            $newLearningProgram = $this
                ->findByProjectAndCompany($destination, $learningProgram->getCompany());

            if (null === $newLearningProgram) {
                $newLearningProgram = new LearningProgram();
                $newLearningProgram
                    ->setCompany($learningProgram->getCompany())
                    ->setProject($destination);

                $this->getEntityManager()->persist($newLearningProgram);
            }

            $activityRealizations = $learningProgram->getActivityRealizations();

            /** @var ActivityRealization $activityRealization */
            foreach ($activityRealizations as $activityRealization) {
                $newActivityRealization = $this->activityRealizationRepository->findOneByProjectAndCode(
                    $destination,
                    $activityRealization->getCode()
                );

                if ($newActivityRealization &&
                    !$newLearningProgram->getActivityRealizations()->contains($newActivityRealization)) {
                    $newLearningProgram->getActivityRealizations()->add($newActivityRealization);
                }
            }
        }
    }

    private function findByProjectAndCompany(Project $project, Company $company)
    {
        return $this->findByProjectQueryBuilder($project)
            ->andWhere('lp.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function deleteFromProjects($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(LearningProgram::class, 'lp')
            ->where('lp.project IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }
}
