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
use App\Entity\Edu\StudentEnrollment;
use App\Entity\ItpModule\StudentAnsweredSurvey;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Entity\Survey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class StudentAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly StudentProgramWorkcenterRepository $studentProgramWorkcenterRepository)
    {
        parent::__construct($registry, StudentAnsweredSurvey::class);
    }

    public function findOneByTrainingProgramAndStudentEnrollment(
        TrainingProgram   $trainingProgram,
        StudentEnrollment $studentEnrollment
    ): ?StudentAnsweredSurvey {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('sas')
            ->from(StudentAnsweredSurvey::class, 'sas')
            ->join('sas.studentProgramWorkcenter', 'spw')
            ->join('spw.studentProgram', 'sp')
            ->join('sp.programGroup', 'pg')
            ->join('pg.programGrade', 'pgr')
            ->join('pgr.trainingProgram', 'tp')
            ->where('tp = :training_program')
            ->andWhere('sas.studentEnrollment = :student_enrollment')
            ->setParameter('training_program', $trainingProgram)
            ->setParameter('student_enrollment', $studentEnrollment)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createNewAnsweredSurvey(Survey $survey, StudentProgramWorkcenter $studentProgramWorkcenter, StudentEnrollment $studentEnrollment): StudentAnsweredSurvey
    {
        $studentSurvey = new AnsweredSurvey();
        $studentSurvey->setSurvey($survey);

        $studentAnsweredSurvey = new StudentAnsweredSurvey();
        $studentAnsweredSurvey
            ->setAnsweredSurvey($studentSurvey)
            ->setStudentProgramWorkcenter($studentProgramWorkcenter)
            ->setStudentEnrollment($studentEnrollment);


        $this->getEntityManager()->persist($studentSurvey);
        $this->getEntityManager()->persist($studentAnsweredSurvey);

        foreach ($survey->getQuestions() as $question) {
            $answeredQuestion = new AnsweredSurveyQuestion();
            $answeredQuestion
                ->setAnsweredSurvey($studentSurvey)
                ->setSurveyQuestion($question);

            $studentSurvey->getAnswers()->add($answeredQuestion);

            $this->getEntityManager()->persist($answeredQuestion);
        }

        return $studentAnsweredSurvey;
    }

    public function findByTrainingProgramAndAcademicYear(TrainingProgram $trainingProgram, ?AcademicYear $academicYear): array
    {
        $qb = $this->createQueryBuilder('sas')
            ->join('sas.studentProgramWorkcenter', 'spw')
            ->join('spw.studentProgram', 'sp')
            ->join('sp.programGroup', 'pg')
            ->join('pg.programGrade', 'pgr')
            ->join('pgr.trainingProgram', 'tp')
            ->join('sas.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->andWhere('tp = :training_program')
            ->setParameter('training_program', $trainingProgram);

        if ($academicYear instanceof AcademicYear) {
            $qb
                ->join('se.group', 'g')
                ->join('g.grade', 'gr')
                ->join('gr.training', 't')
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $qb
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
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
            ->select('spw', 'c', 'w', 'p', 'g', 'gr', 't', 'sp', 'se', 'et', 'etp', 'wt', 'pg', 'pgr', 'tp', 'COUNT(sas)')
            ->join('sp.programGroup', 'pg')
            ->join('pg.programGrade', 'pgr')
            ->join('pgr.trainingProgram', 'tp')
            ->leftJoin(StudentAnsweredSurvey::class, 'sas', 'WITH', 'sas.studentEnrollment = sp.studentEnrollment AND sas.studentProgramWorkcenter = spw')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('p.id')
            ->addOrderBy('tp.name');

        return $queryBuilder;
    }

    public function getStatsByTrainingProgramAndAcademicYear(TrainingProgram $trainingProgram, AcademicYear $academicYear): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('spw', 'c', 'w', 'p', 'g', 'gr', 't', 'sp', 'se', 'et', 'awt', 'aet', 'wt', 'pg', 'pgr', 'tp')
            ->from(StudentProgramWorkcenter::class, 'spw')
            ->join('spw.studentProgram', 'sp')
            ->join('sp.programGroup', 'pg')
            ->join('pg.programGrade', 'pgr')
            ->join('pgr.trainingProgram', 'tp')
            ->join('sp.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('spw.workTutor', 'wt')
            ->join('spw.workcenter', 'w')
            ->join('w.company', 'c')
            ->leftJoin('spw.additionalWorkTutor', 'awt')
            ->join('spw.educationalTutor', 'et')
            ->leftJoin('spw.additionalEducationalTutor', 'aet')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('tp = :training_program')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('training_program', $trainingProgram)
            ->setParameter('academic_year', $academicYear)
            ->leftJoin(StudentAnsweredSurvey::class, 'sas', 'WITH', 'sas.studentEnrollment = se AND sas.studentProgramWorkcenter = spw')
            ->addSelect('COUNT(sas)')
            ->addGroupBy('spw')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('p.id');

        return $queryBuilder->getQuery()->getResult();
    }
}
