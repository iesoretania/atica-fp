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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\TrainingProgram;
use App\Repository\Edu\TeacherRepository as EduTeacherRepository;
use Doctrine\Persistence\ManagerRegistry;

class TeacherRepository extends EduTeacherRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry);
    }

    public function findProgramGroupManagersByTrainingProgram(TrainingProgram $trainingProgram): array {
        return $this->createQueryBuilder('t')
            ->join(ProgramGroup::class, 'pg', 'WITH', 't MEMBER OF pg.managers')
            ->join('pg.programGrade', 'pgg')
            ->where('pgg.trainingProgram = :trainingProgram')
            ->setParameter('trainingProgram', $trainingProgram)
            ->getQuery()
            ->getResult();
    }

    public function findByGroupsOrEducationalTutor(array $groups, AcademicYear $academicYear): array
    {
        $teachers = $this->createQueryBuilder('t')
            ->distinct()
            ->join('t.teachings', 'te')
            ->andWhere('te.group IN (:groups)')
            ->setParameter('groups', $groups)
            ->getQuery()
            ->getResult();

        $educationalTutors = $this->createQueryBuilder('t')
            ->distinct()
            ->join(
                StudentProgramWorkcenter::class,
                'spw',
                'WITH',
                'spw.educationalTutor = t OR spw.additionalEducationalTutor = t'
            )
            ->join(StudentEnrollment::class, 'se')
            ->andWhere('se.group IN (:groups)')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('groups', $groups)
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getResult();

        foreach ($educationalTutors as $educationalTutor) {
            if (!in_array($educationalTutor, $teachers)) {
                array_unshift($teachers, $educationalTutor);
            }
        }

        return $teachers;
    }
}
