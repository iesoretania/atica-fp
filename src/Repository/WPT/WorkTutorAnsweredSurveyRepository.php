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

namespace App\Repository\WPT;

use App\Entity\AnsweredSurvey;
use App\Entity\AnsweredSurveyQuestion;
use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WPT\Agreement;
use App\Entity\WPT\Shift;
use App\Entity\WPT\WorkTutorAnsweredSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WorkTutorAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkTutorAnsweredSurvey::class);
    }

    public function findOneByShiftAndWorkTutor(
        Shift $shift,
        Person $workTutor
    ) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('wtas')
            ->from(WorkTutorAnsweredSurvey::class, 'wtas')
            ->where('wtas.shift = :shift')
            ->andWhere('wtas.workTutor = :person')
            ->setParameter('shift', $shift)
            ->setParameter('person', $workTutor)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createNewAnsweredSurvey(
        Survey $survey,
        Shift $shift,
        Person $workTutor
    ) {
        $studentSurvey = new AnsweredSurvey();
        $studentSurvey->setSurvey($survey);

        $workTutorAnsweredSurvey = new WorkTutorAnsweredSurvey();
        $workTutorAnsweredSurvey
            ->setAnsweredSurvey($studentSurvey)
            ->setShift($shift)
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

    public function getStatsByShift(Shift $shift)
    {$queryBuilder = $this->getEntityManager()->createQueryBuilder()
        ->select('se, pro, a, p, g, wt, w, c, t, gr')
        ->from(Agreement::class, 'a')
        ->distinct()
        ->join('a.shift', 'shi')
        ->join('a.agreementEnrollments', 'ae')
        ->join('ae.studentEnrollment', 'se')
        ->join('se.person', 'p')
        ->join('ae.workTutor', 'wt')
        ->join('a.workcenter', 'w')
        ->join('w.company', 'c')
        ->leftJoin('ae.additionalWorkTutor', 'awt')
        ->join('ae.educationalTutor', 'et')
        ->leftJoin('ae.additionalEducationalTutor', 'aet')
        ->join('se.group', 'g')
        ->join('g.grade', 'gr')
        ->join('gr.training', 't')
        ->where('shi = :shift')
        ->setParameter('project', $shift)
        ->leftJoin(
            WorkTutorAnsweredSurvey::class,
            'wtas',
            'WITH',
            'wtas.workTutor = wt AND wtas.shift = shi'
        )
        ->andWhere('ae.workTutor = wt OR ae.additionalWorkTutor = wt')
        ->addSelect('COUNT(wtas)')
        ->addGroupBy('ae')
        ->addOrderBy('wt.lastName')
        ->addOrderBy('wt.firstName')
        ->addOrderBy('pro.name')
        ->addOrderBy('p.lastName')
        ->addOrderBy('p.firstName');

        return $queryBuilder->getQuery()->getResult();
    }

    public function findByShift(Shift $shift)
    {
        $qb = $this->createQueryBuilder('wtas')
            ->join('wtas.workTutor', 'wt')
            ->andWhere('wtas.shift = :shift');

        return $qb
            ->orderBy('wt.lastName')
            ->addOrderBy('wt.firstName')
            ->getQuery()
            ->getResult();
    }
}
