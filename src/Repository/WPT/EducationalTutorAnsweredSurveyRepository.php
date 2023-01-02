<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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
use App\Entity\Edu\Teacher;
use App\Entity\Survey;
use App\Entity\WPT\EducationalTutorAnsweredSurvey;
use App\Entity\WPT\Shift;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EducationalTutorAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EducationalTutorAnsweredSurvey::class);
    }

    public function findOneByShiftAndTeacher(
        Shift $shift,
        Teacher $teacher
    ) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('etas')
            ->from(EducationalTutorAnsweredSurvey::class, 'etas')
            ->where('etas.shift = :shift')
            ->andWhere('etas.teacher = :teacher')
            ->setParameter('shift', $shift)
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createNewAnsweredSurvey(
        Survey $survey,
        Shift $shift,
        Teacher $teacher
    ) {
        $studentSurvey = new AnsweredSurvey();
        $studentSurvey->setSurvey($survey);

        $educationalTutorAnsweredSurvey = new EducationalTutorAnsweredSurvey();
        $educationalTutorAnsweredSurvey
            ->setAnsweredSurvey($studentSurvey)
            ->setShift($shift)
            ->setTeacher($teacher);

        $this->getEntityManager()->persist($studentSurvey);
        $this->getEntityManager()->persist($educationalTutorAnsweredSurvey);

        foreach ($survey->getQuestions() as $question) {
            $answeredQuestion = new AnsweredSurveyQuestion();
            $answeredQuestion
                ->setAnsweredSurvey($studentSurvey)
                ->setSurveyQuestion($question);

            $studentSurvey->getAnswers()->add($answeredQuestion);

            $this->getEntityManager()->persist($answeredQuestion);
        }

        return $educationalTutorAnsweredSurvey;
    }
}
