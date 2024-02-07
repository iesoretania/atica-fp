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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\WPT\AgreementEnrollment;
use App\Entity\WPT\EducationalTutorAnsweredSurvey;
use App\Entity\WPT\Shift;
use App\Repository\Edu\TeacherRepository;
use App\Security\OrganizationVoter;
use App\Security\WPT\WPTOrganizationVoter;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class WPTTeacherRepository extends TeacherRepository
{
    private $security;
    private $shiftRepository;

    public function __construct(
        ManagerRegistry $registry,
        Security $security,
        ShiftRepository $shiftRepository
    ) {
        parent::__construct($registry);
        $this->security = $security;
        $this->shiftRepository = $shiftRepository;
    }

    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('t')
            ->join('t.person', 'p')
            ->join(
                AgreementEnrollment::class,
                'ae',
                'WITH',
                'ae.educationalTutor = t OR ae.additionalEducationalTutor = t'
            )
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function findEducationalTutorsByStudentEnrollmentAndShift(StudentEnrollment $studentEnrollment, Shift $shift)
    {
        return $this->createQueryBuilder('t')
            ->distinct(true)
            ->join(AgreementEnrollment::class, 'ae', 'WITH', 'ae.educationalTutor = t OR ae.additionalEducationalTutor = t')
            ->join('ae.agreement', 'a')
            ->join('t.person', 'p')
            ->where('ae.studentEnrollment = :student_enrollment')
            ->andWhere('a.shift = :shift')
            ->setParameter('student_enrollment', $studentEnrollment)
            ->setParameter('shift', $shift)
            ->getQuery()
            ->getResult();
    }

    public function findByGroups($groups)
    {
        return $this->createQueryBuilder('t')
            ->distinct(true)
            ->join(AgreementEnrollment::class, 'ae', 'WITH', 'ae.educationalTutor = t OR ae.additionalEducationalTutor = t')
            ->join('ae.studentEnrollment', 'se')
            ->andWhere('se.group IN (:groups)')
            ->setParameter('groups', $groups)
            ->getQuery()
            ->getResult();
    }

    public function findTeachersShiftDataByAcademicYearAndTeacherFilteredQueryBuilder(
        $q,
        AcademicYear $academicYear,
        ?Teacher $teacher
    ) {
        $organization = $academicYear->getOrganization();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('t.id AS teacherId')
            ->addSelect('p.firstName AS firstName')
            ->addSelect('p.lastName AS lastName')
            ->addSelect('shi.id AS shiftId')
            ->addSelect('shi.name AS shiftName')
            ->addSelect('shi.lock AS shiftLocked')
            ->addSelect('COUNT(etas)')
            ->from(Teacher::class, 't')
            ->join(
                AgreementEnrollment::class,
                'ae',
                'WITH',
                'ae.educationalTutor = t OR ae.additionalEducationalTutor = t'
            )
            ->join('ae.agreement', 'a')
            ->join(Shift::class, 'shi', 'WITH', 'a.shift = shi')
            ->join('t.person', 'p')
            ->leftJoin(
                EducationalTutorAnsweredSurvey::class,
                'etas',
                'WITH',
                'etas.teacher = t AND etas.shift = shi'
            )
            ->addGroupBy('t, shi')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('shi.name');

        if ($q) {
            $queryBuilder
                ->orWhere('shi.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWptManager = $this->security->isGranted(WPTOrganizationVoter::WPT_MANAGER, $organization);

        if (!$isManager && $isWptManager) {
            $shifts = $this->shiftRepository->findByHeadOfDepartment($teacher);
            $queryBuilder
                ->andWhere('shi IN (:shifts) OR t = :teacher')
                ->setParameter('shifts', $shifts)
                ->setParameter('teacher', $teacher);
        }

        if (!$isWptManager && !$isManager) {
            $queryBuilder
                ->andWhere('t = :teacher')
                ->setParameter('teacher', $teacher);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        return $queryBuilder;
    }

    public function getStatsByShiftWithAnsweredSurvey(Shift $shift) {
        $data = $this->createQueryBuilder('t')
            ->select('t')
            ->addSelect('etas')
            ->join('t.person', 'p')
            ->join(
                AgreementEnrollment::class,
                'ae',
                'WITH',
                'ae.educationalTutor = t OR ae.additionalEducationalTutor = t'
            )
            ->join('ae.agreement', 'a')
            ->join(Shift::class, 's', 'WITH', 'a.shift = s')
            ->leftJoin(
                EducationalTutorAnsweredSurvey::class,
                'etas',
                'WITH',
                'etas.teacher = t AND etas.shift = s'
            )
            ->where('s = :shift')
            ->setParameter('shift', $shift)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->groupBy('t, etas, s')
            ->getQuery()
            ->getResult();

        return array_chunk($data, 2);
    }
}
