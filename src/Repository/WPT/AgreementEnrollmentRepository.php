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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\WPT\AgreementEnrollment;
use App\Entity\WPT\Shift;
use App\Security\OrganizationVoter;
use App\Security\WPT\WPTOrganizationVoter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class AgreementEnrollmentRepository extends ServiceEntityRepository
{
    private $security;
    private $wptGroupRepository;

    public function __construct(
        ManagerRegistry $registry,
        Security $security,
        WPTGroupRepository $wptGroupRepository
    ) {
        parent::__construct($registry, AgreementEnrollment::class);
        $this->security = $security;
        $this->wptGroupRepository = $wptGroupRepository;
    }

    public function findByStudentEnrollment(StudentEnrollment $studentEnrollment)
    {
        return $this->createQueryBuilder('ae')
            ->join('ae.agreement', 'a')
            ->where('ae.studentEnrollment = :student_enrollment')
            ->setParameter('student_enrollment', $studentEnrollment)
            ->orderBy('a.startDate')
            ->addOrderBy('a.endDate')
            ->addOrderBy('a.signDate')
            ->getQuery()
            ->getResult();
    }

    public function findByEducationalTutor(Teacher $teacher)
    {
        return $this->createQueryBuilder('ae')
            ->where('ae.educationalTutor = :teacher OR ae.additionalEducationalTutor = :teacher')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYearAndPersonFilterQueryBuilder(
        $q,
        ?AcademicYear $academicYear,
        Person $person
    ) {
        $organization = $academicYear->getOrganization();
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWptManager = $this->security->isGranted(WPTOrganizationVoter::WPT_MANAGE, $organization);

        $queryBuilder = $this->createQueryBuilder('ae')
            ->select('se, shi, a, ae, p, g, wt, w, c')
            ->distinct()
            ->join('ae.agreement', 'a')
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
            ->join('gr.training', 't');

        if ($q) {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('shi.name LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $groups = [];

        if (!$isManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento o tutor de grupo -> ver los acuerdos de los
            // estudiantes de sus grupos
            $groups = $this->wptGroupRepository
                ->findByAcademicYearAndWPTGroupTutorOrDepartmentHeadPerson($academicYear, $person);
        }

        // ver siempre las propias
        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR se.person = :person OR wt = :person OR awt = :person' .
                ' OR et.person = :person OR aet.person = :person')
                ->setParameter('groups', $groups)
                ->setParameter('person', $person);
        }
        if (!$isWptManager && !$isManager && !$groups) {
            $queryBuilder
                ->andWhere('se.person = :person OR wt = :person OR awt = :person OR et = :person OR aet = :person')
                ->setParameter('person', $person);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        return $queryBuilder;
    }

    public function countTeacherAndShiftQueryBuilder(Teacher $teacher, Shift $shift)
    {
        return $this->createQueryBuilder('ae')
            ->select('COUNT(ae)')
            ->join('ae.agreement', 'a')
            ->join('ae.educationalTutor', 't')
            ->leftJoin('ae.additionalEducationalTutor', 'aet')
            ->andWhere('a.shift = :shift')
            ->andWhere('t = :teacher OR aet = :teacher')
            ->setParameter('teacher', $teacher)
            ->setParameter('shift', $shift);
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $person
     *
     * @return int
     */
    public function countTeacherAndShift(Teacher $teacher, Shift $shift)
    {
        try {
            return $this->countTeacherAndShiftQueryBuilder($teacher, $shift)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException $e) {
        }

        return 0;
    }
}
