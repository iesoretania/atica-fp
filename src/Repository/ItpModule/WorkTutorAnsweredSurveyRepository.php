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
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\ItpModule\WorkTutorAnsweredSurvey;
use App\Entity\Person;
use App\Entity\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class WorkTutorAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository)
    {
        parent::__construct($registry, WorkTutorAnsweredSurvey::class);
    }

    public function findOneByTrainingProgramAcademicYearAndWorkTutor(
        TrainingProgram $trainingProgram,
        AcademicYear    $academicYear,
        Person          $workTutor
    ) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('wtas')
            ->from(WorkTutorAnsweredSurvey::class, 'wtas')
            ->where('wtas.trainingProgram = :training_program')
            ->andWhere('wtas.academicYear = :academic_year')
            ->andWhere('wtas.workTutor = :person')
            ->setParameter('training_program', $trainingProgram)
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $workTutor)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createNewAnsweredSurvey(
        Survey          $survey,
        TrainingProgram $trainingProgram,
        AcademicYear    $academicYear,
        Person          $workTutor
    ): WorkTutorAnsweredSurvey
    {
        $workTutorSurvey = new AnsweredSurvey();
        $workTutorSurvey->setSurvey($survey);

        $workTutorAnsweredSurvey = new WorkTutorAnsweredSurvey();
        $workTutorAnsweredSurvey
            ->setAnsweredSurvey($workTutorSurvey)
            ->setTrainingProgram($trainingProgram)
            ->setAcademicYear($academicYear)
            ->setWorkTutor($workTutor);


        $this->getEntityManager()->persist($workTutorSurvey);
        $this->getEntityManager()->persist($workTutorAnsweredSurvey);

        foreach ($survey->getQuestions() as $question) {
            $answeredQuestion = new AnsweredSurveyQuestion();
            $answeredQuestion
                ->setAnsweredSurvey($workTutorSurvey)
                ->setSurveyQuestion($question);

            $workTutorSurvey->getAnswers()->add($answeredQuestion);

            $this->getEntityManager()->persist($answeredQuestion);
        }

        return $workTutorAnsweredSurvey;
    }

    public function findByTrainingProgramAndAcademicYear(TrainingProgram $trainingProgram, ?AcademicYear $academicYear)
    {
        $qb = $this->createQueryBuilder('wtas')
            ->join('wtas.workTutor', 'wt')
            ->andWhere('wtas.trainingProgram = :training_program')
            ->setParameter('training_program', $trainingProgram);

        if ($academicYear instanceof AcademicYear) {
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

    public function createStatsByAcademicYearAndPersonFilterQueryBuilder(
        ?AcademicYear $academicYear,
        bool $isManager,
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
            ->select('spw', 'c', 'w', 'p', 'g', 'gr', 't', 'sp', 'se', 'et', 'etp', 'wt', 'pg', 'pgr', 'tp', 'COUNT(wtas)')
            ->join('sp.programGroup', 'pg')
            ->join('pg.programGrade', 'pgr')
            ->join('pgr.trainingProgram', 'tp')
            ->join('spw.workTutor', 'wtp')
            ->leftJoin('spw.additionalWorkTutor', 'awtp')
            ->leftJoin(WorkTutorAnsweredSurvey::class, 'wtas', 'WITH', '(wtas.workTutor = wtp OR wtas.workTutor = awtp) AND wtas.trainingProgram = tp')
            ->addOrderBy('wtp.lastName')
            ->addOrderBy('wtp.firstName')
            ->addOrderBy('wtp.id')
            ->addOrderBy('tp.name');

        return $queryBuilder;
    }
}
