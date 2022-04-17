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

namespace App\Repository\WLT;

use App\Entity\AnsweredSurvey;
use App\Entity\AnsweredSurveyQuestion;
use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\Project;
use App\Entity\WLT\WorkTutorAnsweredSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkTutorAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkTutorAnsweredSurvey::class);
    }

    public function findOneByProjectAcademicYearAndWorkTutor(
        Project $project,
        AcademicYear $academicYear,
        Person $workTutor
    ) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('wtas')
            ->from(WorkTutorAnsweredSurvey::class, 'wtas')
            ->where('wtas.project = :project')
            ->andWhere('wtas.academicYear = :academic_year')
            ->andWhere('wtas.workTutor = :person')
            ->setParameter('project', $project)
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $workTutor)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createNewAnsweredSurvey(
        Survey $survey,
        Project $project,
        AcademicYear $academicYear,
        Person $workTutor
    ) {
        $studentSurvey = new AnsweredSurvey();
        $studentSurvey->setSurvey($survey);

        $workTutorAnsweredSurvey = new WorkTutorAnsweredSurvey();
        $workTutorAnsweredSurvey
            ->setAnsweredSurvey($studentSurvey)
            ->setProject($project)
            ->setAcademicYear($academicYear)
            ->setWorkTutor($workTutor);


        $this->getEntityManager()->persist($studentSurvey);
        $this->getEntityManager()->persist($workTutorAnsweredSurvey);

        foreach ($survey->getQuestions() as $question) {
            $answeredQuestion = new AnsweredSurveyQuestion();
            $answeredQuestion
                ->setAnsweredSurvey($studentSurvey)
                ->setSurveyQuestion($question);

            $studentSurvey->getAnswers()->add($answeredQuestion);

            $this->getEntityManager()->persist($answeredQuestion);
        }

        return $workTutorAnsweredSurvey;
    }

    public function getStatsByProjectAndAcademicYear(Project $project, ?AcademicYear $academicYear)
    {$queryBuilder = $this->getEntityManager()->createQueryBuilder()
        ->select('se, pro, a, p, g, wt, w, c, t, gr')
        ->from(Agreement::class, 'a')
        ->distinct()
        ->join('a.project', 'pro')
        ->join('a.studentEnrollment', 'se')
        ->join('se.person', 'p')
        ->join('a.workTutor', 'wt')
        ->join('a.workcenter', 'w')
        ->join('w.company', 'c')
        ->leftJoin('a.additionalWorkTutor', 'awt')
        ->join('a.educationalTutor', 'et')
        ->leftJoin('a.additionalEducationalTutor', 'aet')
        ->join('se.group', 'g')
        ->join('g.grade', 'gr')
        ->join('gr.training', 't')
        ->where('pro = :project')
        ->setParameter('project', $project)
        ->leftJoin(
            WorkTutorAnsweredSurvey::class,
            'wtas',
            'WITH',
            'wtas.workTutor = wt AND wtas.project = pro'
        )
        ->andWhere('a.workTutor = wt OR a.additionalWorkTutor = wt')
        ->addSelect('COUNT(wtas)')
        ->addGroupBy('a')
        ->addOrderBy('wt.lastName')
        ->addOrderBy('wt.firstName')
        ->addOrderBy('pro.name')
        ->addOrderBy('p.lastName')
        ->addOrderBy('p.firstName');

        if ($academicYear) {
            $queryBuilder
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByProjectAndAcademicYear(Project $project, ?AcademicYear $academicYear)
    {
        $qb = $this->createQueryBuilder('wtas')
            ->join('wtas.workTutor', 'wt')
            ->andWhere('wtas.project = :project')
            ->setParameter('project', $project);

        if ($academicYear) {
            $qb
                ->andWhere('wtas.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $qb
            ->orderBy('wt.lastName')
            ->addOrderBy('wt.firstName')
            ->getQuery()
            ->getResult();
    }
}
