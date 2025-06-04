<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Repository\ItpModule;

use App\Entity\AnsweredSurvey;
use App\Entity\AnsweredSurveyQuestion;
use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\EducationalTutorAnsweredSurvey;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Entity\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class EducationalTutorAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository)
    {
        parent::__construct($registry, EducationalTutorAnsweredSurvey::class);
    }

    public function findOneByTrainingProgramAndTeacher(
        TrainingProgram $trainingProgram,
        Teacher         $teacher
    ) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('etas')
            ->from(EducationalTutorAnsweredSurvey::class, 'etas')
            ->where('etas.trainingProgram = :training_program')
            ->andWhere('etas.teacher = :teacher')
            ->setParameter('training_program', $trainingProgram)
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getOneOrNullResult();
    }


    public function createNewAnsweredSurvey(
        Survey $survey,
        TrainingProgram $trainingProgram,
        Teacher $teacher
    ): EducationalTutorAnsweredSurvey
    {
        $studentSurvey = new AnsweredSurvey();
        $studentSurvey->setSurvey($survey);

        $educationalTutorAnsweredSurvey = new EducationalTutorAnsweredSurvey();
        $educationalTutorAnsweredSurvey
            ->setAnsweredSurvey($studentSurvey)
            ->setTrainingProgram($trainingProgram)
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


    public function createStatsByAcademicYearAndPersonFilterQueryBuilder(
        ?AcademicYear $academicYear,
        bool $isManager,
        bool $isItpManager,
        Person $person,
        ?string $q,
    ): QueryBuilder {
        $queryBuilder = $this->studentProgramWorkcenterRepository->createGradingQueryBuilder(
            $academicYear,
            $person,
            $isManager,
            $q
        );

        $queryBuilder
            ->select('spw', 'c', 'w', 'p', 'g', 'gr', 't', 'sp', 'se', 'et', 'etp', 'wt', 'pg', 'pgr', 'tp', 'COUNT(etas)')
            ->join('sp.programGroup', 'pg')
            ->join('pg.programGrade', 'pgr')
            ->join('pgr.trainingProgram', 'tp')
            ->leftJoin(EducationalTutorAnsweredSurvey::class, 'etas', 'WITH', '(etas.teacher = et OR etas.teacher = aet) AND etas.trainingProgram = tp')
            ->orderBy('etp.lastName')
            ->addOrderBy('etp.firstName')
            ->addOrderBy('etp.id')
            ->addOrderBy('tp.name');

        if (!$isItpManager) {
            $queryBuilder
                ->andWhere('etp = :person OR aetp = :person')
                ->setParameter('person', $person);
        }
        return $queryBuilder;
    }
}
