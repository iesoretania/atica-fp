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

namespace App\Repository\WLT;

use App\Entity\AnsweredSurvey;
use App\Entity\AnsweredSurveyQuestion;
use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\Project;
use App\Entity\WLT\StudentAnsweredSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StudentAnsweredSurveyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly AgreementRepository $agreementRepository)
    {
        parent::__construct($registry, StudentAnsweredSurvey::class);
    }

    public function findOneByProjectAndStudentEnrollment(
        Project $project,
        StudentEnrollment $studentEnrollment
    ) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('sas')
            ->from(StudentAnsweredSurvey::class, 'sas')
            ->where('sas.project = :project')
            ->andWhere('sas.studentEnrollment = :student_enrollment')
            ->setParameter('project', $project)
            ->setParameter('student_enrollment', $studentEnrollment)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createNewAnsweredSurvey(Survey $survey, Project $project, StudentEnrollment $studentEnrollment): StudentAnsweredSurvey
    {
        $studentSurvey = new AnsweredSurvey();
        $studentSurvey->setSurvey($survey);

        $studentAnsweredSurvey = new StudentAnsweredSurvey();
        $studentAnsweredSurvey
            ->setAnsweredSurvey($studentSurvey)
            ->setProject($project)
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

    public function findByProjectAndAcademicYear(Project $project, ?AcademicYear $academicYear)
    {
        $qb = $this->createQueryBuilder('sas')
            ->join('sas.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->andWhere('sas.project = :project')
            ->setParameter('project', $project);

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

    public function findByAcademicYearAndPersonFilterQueryBuilder(
        $q,
        ?AcademicYear $academicYear,
        Person $person
    ) {
        $queryBuilder = $this->agreementRepository->findByAcademicYearAndPersonFilterQueryBuilder(
            $q,
            $academicYear,
            $person
        );

        $queryBuilder
            ->leftJoin(StudentAnsweredSurvey::class, 'sas', 'WITH', 'sas.studentEnrollment = se AND sas.project = pro')
            ->addSelect('COUNT(sas)')
            ->addGroupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('p.id')
            ->addOrderBy('pro.name');

        return $queryBuilder;
    }

    public function getStatsByProjectAndAcademicYear(Project $project, ?AcademicYear $academicYear)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('se, pro, a, p, g, wt, w, c')
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
            ->leftJoin(StudentAnsweredSurvey::class, 'sas', 'WITH', 'sas.studentEnrollment = se AND sas.project = pro')
            ->addSelect('COUNT(sas)')
            ->addGroupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('p.id')
            ->addOrderBy('pro.name');

        if ($academicYear instanceof AcademicYear) {
            $queryBuilder
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
