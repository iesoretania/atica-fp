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
use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Person;
use App\Entity\Survey;
use App\Entity\WPT\Agreement;
use App\Entity\WPT\Shift;
use App\Entity\WPT\StudentAnsweredSurvey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StudentAnsweredSurveyRepository extends ServiceEntityRepository
{
    private $shiftRepository;
    private $agreementEnrollmentRepository;

    public function __construct(
        ManagerRegistry $registry,
        ShiftRepository $shiftRepository,
        AgreementEnrollmentRepository $agreementEnrollmentRepository
    ) {
        parent::__construct($registry, StudentAnsweredSurvey::class);
        $this->shiftRepository = $shiftRepository;
        $this->agreementEnrollmentRepository = $agreementEnrollmentRepository;
    }

    public function findOneByShiftAndStudentEnrollment(
        Shift $shift,
        StudentEnrollment $studentEnrollment
    ) {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('sas')
            ->from(StudentAnsweredSurvey::class, 'sas')
            ->where('sas.shift = :shift')
            ->andWhere('sas.studentEnrollment = :student_enrollment')
            ->setParameter('shift', $shift)
            ->setParameter('student_enrollment', $studentEnrollment)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createNewAnsweredSurvey(Survey $survey, Shift $shift, StudentEnrollment $studentEnrollment)
    {
        $studentSurvey = new AnsweredSurvey();
        $studentSurvey->setSurvey($survey);

        $studentAnsweredSurvey = new StudentAnsweredSurvey();
        $studentAnsweredSurvey
            ->setAnsweredSurvey($studentSurvey)
            ->setShift($shift)
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

    public function findByAcademicYearAndPersonFilterQueryBuilder(
        $q,
        ?AcademicYear $academicYear,
        Person $person
    ) {
        $queryBuilder = $this->agreementEnrollmentRepository->findByAcademicYearAndPersonFilterQueryBuilder(
            $q,
            $academicYear,
            $person
        );

        $queryBuilder
            ->leftJoin(StudentAnsweredSurvey::class, 'sas', 'WITH', 'sas.studentEnrollment = se AND sas.shift = shi')
            ->addSelect('COUNT(sas)')
            ->addGroupBy('ae')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('p.id')
            ->addOrderBy('shi.name');

        return $queryBuilder;
    }

    public function getStatsByShiftAndAcademicYear(Shift $shift, ?AcademicYear $academicYear)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('se, shi, a, p, g, wt, w, c')
            ->from(Agreement::class, 'a')
            ->distinct()
            ->join('a.shift', 'shi')
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
            ->setParameter('shift', $shift)
            ->leftJoin(StudentAnsweredSurvey::class, 'sas', 'WITH', 'sas.studentEnrollment = se AND sas.shift = shi')
            ->addSelect('COUNT(sas)')
            ->addGroupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('p.id')
            ->addOrderBy('shi.name');

        if ($academicYear) {
            $queryBuilder
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
