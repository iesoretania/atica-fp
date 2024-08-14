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

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\AgreementActivityRealization;
use App\Entity\WLT\Meeting;
use App\Entity\WLT\Project;
use App\Entity\WLT\WorkDay;
use App\Security\OrganizationVoter;
use App\Security\WLT\WLTOrganizationVoter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class AgreementRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly WorkDayRepository $workDayRepository,
        private readonly WLTGroupRepository $wltGroupRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly Security $security
    ) {
        parent::__construct($registry, Agreement::class);
    }

    /**
     *
     * @return QueryBuilder
     */
    public function findByAcademicYearAndStudentQueryBuilder(AcademicYear $academicYear, Person $student)
    {
        return $this->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'sr')
            ->join('sr.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('sr.person = :student')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('student', $student)
            ->setParameter('academic_year', $academicYear);
    }

    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('a')
            ->addSelect('sr')
            ->addSelect('p')
            ->addSelect('g')
            ->join('a.studentEnrollment', 'sr')
            ->join('sr.person', 'p')
            ->join('sr.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYearAndEducationalTutorOrDepartmentHead(AcademicYear $academicYear, Teacher $teacher)
    {

        return $this->createQueryBuilder('a')
            ->distinct(true)
            ->join('a.project', 'p')
            ->join('p.groups', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->leftJoin('t.department', 'd')
            ->leftJoin('d.head', 'h')
            ->where('t.academicYear = :academic_year')
            ->andWhere('h.person = :person OR a.educationalTutor = :teacher OR a.additionalEducationalTutor = :teacher')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $teacher->getPerson())
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    /**
     *
     * @return int
     */
    public function countAcademicYearAndStudentPerson(AcademicYear $academicYear, Person $student)
    {
        try {
            return $this->findByAcademicYearAndStudentQueryBuilder($academicYear, $student)
                ->select('COUNT(a)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }

        return 0;
    }

    /**
     *
     * @return int
     */
    public function countAcademicYearAndWorkTutorPerson(AcademicYear $academicYear, Person $workTutor)
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a)')
                ->join('a.workcenter', 'w')
                ->join('a.studentEnrollment', 'sr')
                ->join('sr.group', 'g')
                ->join('g.grade', 'gr')
                ->join('gr.training', 't')
                ->where('a.workTutor = :work_tutor OR a.additionalWorkTutor = :work_tutor')
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('work_tutor', $workTutor)
                ->setParameter('academic_year', $academicYear)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }

        return 0;
    }

    /**
     *
     * @return QueryBuilder
     */
    private function countAcademicYearAndEducationalTutorPersonQueryBuilder(AcademicYear $academicYear, Person $person)
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a)')
            ->join('a.educationalTutor', 't')
            ->leftJoin('a.additionalEducationalTutor', 'aet')
            ->andWhere('t.academicYear = :academic_year')
            ->andWhere('t.person = :educational_tutor OR aet.person = :educational_tutor')
            ->setParameter('educational_tutor', $person)
            ->setParameter('academic_year', $academicYear);
    }

    /**
     *
     * @return int
     */
    public function countAcademicYearAndEducationalTutorPerson(AcademicYear $academicYear, Person $person)
    {
        try {
            return $this->countAcademicYearAndEducationalTutorPersonQueryBuilder($academicYear, $person)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }

        return 0;
    }

    /**
     * @param $items
     * @return Agreement[]
     */
    public function findAllInListByIdAndProject(
        $items,
        Project $project
    ) {
        return $this->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('se.person', 'p')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->where('a.id IN (:items)')
            ->andWhere('a.project = :project')
            ->setParameter('items', $items)
            ->setParameter('project', $project)
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $items
     * @return Agreement[]
     */
    public function findAllInListByNotIdAndProject(
        $items,
        Project $project
    ) {
        return $this->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('se.person', 'p')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->where('a.id NOT IN (:items)')
            ->andWhere('a.project = :project')
            ->setParameter('items', $items)
            ->setParameter('project', $project)
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function updateDates(Agreement $agreement): void
    {
        $workDays = $this->workDayRepository->findByAgreement($agreement);
        if ((is_countable($workDays) ? count($workDays) : 0) === 0) {
            return;
        }
        /** @var WorkDay $first */
        $first = $workDays[0];
        /** @var WorkDay $last */
        $last = $workDays[(is_countable($workDays) ? count($workDays) : 0) - 1];
        $agreement
            ->setStartDate($first->getDate())
            ->setEndDate($last->getDate());

        $this->getEntityManager()->flush();
    }

    public function cloneCalendarFromAgreement(Agreement $destination, Agreement $source, $overwrite = false): void
    {
        $workDays = $this->workDayRepository->findByAgreement($source);
        if ((is_countable($workDays) ? count($workDays) : 0) === 0) {
            return;
        }

        $utc = new \DateTimeZone('UTC');

        /** @var WorkDay $workDay */
        foreach ($workDays as $workDay) {
            $newDate = new \DateTimeImmutable($workDay->getDate()->format('Y/m/d'), $utc);
            $newWorkDay = $this->workDayRepository->findOneByAgreementAndDate($destination, $newDate);
            if (null === $newWorkDay) {
                $newWorkDay = new WorkDay();
                $newWorkDay
                    ->setAgreement($destination)
                    ->setDate(new \DateTime($newDate->format('Y/m/d'), $utc))
                    ->setHours($workDay->getHours());
                $this->getEntityManager()->persist($newWorkDay);
            } elseif ($overwrite) {
                $newWorkDay->setHours($workDay->getHours());
            } else {
                $newWorkDay->setHours($newWorkDay->getHours() + $workDay->getHours());
            }
        }
    }

    /**
     * @param Agreement[]
     */
    public function deleteFromList($list): bool
    {
        $em = $this->getEntityManager();

        $em->createQueryBuilder()
            ->delete(AgreementActivityRealization::class, 'aar')
            ->where('aar.agreement IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->delete(WorkDay::class, 'wd')
            ->where('wd.agreement IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();

        $em->createQueryBuilder()
            ->delete(Agreement::class, 'a')
            ->where('a IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();

        return true;
    }

    public function meetingStatsByTeacherAndProject(Teacher $teacher, Project $project)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->addSelect('se')
            ->addSelect('p')
            /*->addSelect(
                'SUM(CASE WHEN DATE(m.dateTime) >= a.startDate AND DATE(m.dateTime) <= a.endDate THEN 1 ELSE 0 END)'
            )*/
            ->addSelect(
                'COUNT(m)'
            )
            ->addSelect('COUNT(m.dateTime)')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->leftJoin(Meeting::class, 'm', 'WITH', 'se MEMBER OF m.studentEnrollments')
            ->leftJoin(Teacher::class, 't', 'WITH', 't MEMBER OF m.teachers')
            ->groupBy('a')
            ->andWhere('t = :teacher AND a.project = :project')
            ->setParameter('teacher', $teacher)
            ->setParameter('project', $project)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->getQuery()
            ->getResult();
    }

    public function attendanceStatsByProjectAndAcademicYear(Project $project, AcademicYear $academicYear = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a')
            ->addSelect('w')
            ->addSelect('c')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect('g')
            ->addSelect('SUM(wd.hours)')
            ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 1 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN wd.locked * wd.hours ELSE 0 END)')
            ->addSelect('COUNT(wd.hours)')
            ->addSelect('SUM(CASE WHEN wd.absence = 0 THEN wd.locked ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 1 THEN wd.locked ELSE 0 END)')
            ->addSelect('SUM(CASE WHEN wd.absence = 2 THEN wd.locked ELSE 0 END)')
            ->leftJoin('a.workDays', 'wd')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name')
            ->where('a.project = :project')
            ->setParameter('project', $project);

        if ($academicYear instanceof AcademicYear) {
            $qb
                ->andWhere('tr.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function setQueryBuilderFilterByOrganizationPersonProjectAndReturnProjects(
        QueryBuilder $queryBuilder,
        Organization $organization,
        Person $person,
        Project $project = null
    ) {
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);
        $isWorkTutor = $this->security->isGranted(WLTOrganizationVoter::WLT_WORK_TUTOR, $organization);

        $projects = [];
        if ($isWltManager) {
            if (!$isManager) {
                $projects = $this->projectRepository->findByOrganizationAndManagerPerson($organization, $person);
            } else {
                $projects = $this->projectRepository->findByOrganization($organization);
            }
        }

        if (!$isManager && !$isWltManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor
            $groups =
                $this->wltGroupRepository->findByOrganizationAndPerson($organization, $person);

            if (!$groups->isEmpty()) {
                $queryBuilder
                    ->andWhere('g IN (:groups)')
                    ->setParameter('groups', $groups);
            }

            // si solo es tutor laboral, necesita ser el tutor para verlo
            if ($isWorkTutor) {
                $queryBuilder
                    ->andWhere('a.workTutor = :person')
                    ->setParameter('person', $person);
            }

            $queryBuilder
                ->orWhere('p = :person')
                ->setParameter('person', $person);
        }

        if ($project instanceof Project) {
            $queryBuilder
                ->andWhere('a.project = :project')
                ->setParameter('project', $project);
        } elseif ($projects && !$isManager) {
            $queryBuilder
                ->andWhere('a.project IN (:projects)')
                ->setParameter('projects', $projects);
        }

        $queryBuilder
            ->andWhere('pro.organization = :organization')
            ->setParameter('organization', $organization);

        return $projects;
    }

    public function setQueryBuilderFilterByOrganizationManagerPersonProjectAndReturnProjects(
        QueryBuilder $queryBuilder,
        Organization $organization,
        Person $person,
        Project $project = null
    ) {
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        $projects = [];
        if ($isWltManager) {
            if (!$isManager) {
                $projects = $this->projectRepository->findByOrganizationAndManagerPerson($organization, $person);
            } else {
                $projects = $this->projectRepository->findByOrganization($organization);
            }
        }

        if ($project instanceof Project) {
            $queryBuilder
                ->andWhere('a.project = :project')
                ->setParameter('project', $project);
        } elseif ($projects && !$isManager) {
            $queryBuilder
                ->andWhere('a.project IN (:projects)')
                ->setParameter('projects', $projects);
        }

        $queryBuilder
            ->andWhere('pro.organization = :organization')
            ->setParameter('organization', $organization);

        return $projects;
    }

    public function findByAcademicYearAndPersonFilterQueryBuilder(
        ?string $q,
        AcademicYear $academicYear,
        Person $person
    ) {
        $organization = $academicYear->getOrganization();
        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        $queryBuilder = $this->createQueryBuilder('a')
            ->select('a, se, pro, p, g, wt, w, c')
            ->distinct()
            ->join('a.project', 'pro')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->join('a.workTutor', 'wt')
            ->join('a.workcenter', 'w')
            ->join('w.company', 'c')
            ->join('a.educationalTutor', 'et')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 't')
            ->leftJoin('a.additionalEducationalTutor', 'aet')
            ->leftJoin('a.additionalWorkTutor', 'awt');

        if ($q) {
            $queryBuilder
                ->orWhere('g.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->orWhere('pro.name LIKE :tq')
                ->orWhere('w.name LIKE :tq')
                ->orWhere('c.name LIKE :tq')
                ->orWhere('wt.firstName LIKE :tq')
                ->orWhere('wt.lastName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $groups = [];
        $projects = [];

        if (!$isWltManager && !$isManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento o tutor de grupo  -> ver los acuerdos de los
            // estudiantes de sus grupos
            $groups = $this->wltGroupRepository
                ->findByAcademicYearAndGroupTutorOrDepartmentHeadPerson($academicYear, $person);
        } elseif ($isWltManager) {
            $projects = $this->projectRepository->findByManager($person);
        }

        // ver siempre las propias
        if ($groups) {
            $queryBuilder
                ->andWhere('se.group IN (:groups) OR se.person = :person OR wt = :person OR awt = :person' .
                           ' OR et.person = :person OR aet.person = :person')
                ->setParameter('groups', $groups)
                ->setParameter('person', $person);
        }
        if ($projects) {
            $queryBuilder
                ->andWhere('pro IN (:projects) OR se.person = :person OR wt = :person OR awt = :person' .
                           ' OR et.person = :person OR aet.person = :person')
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }

        if (!$isWltManager && !$isManager && !$projects && !$groups) {
            $queryBuilder
                ->andWhere('se.person = :person OR wt = :person OR awt = :person OR et.person = :person OR aet.person = :person')
                ->setParameter('person', $person);
        }

        $queryBuilder
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        return $queryBuilder;
    }

    public function countAcademicYearAndEducationalTutorPersonAndProject(
        AcademicYear $academicYear,
        Person $person,
        Project $project
    ) {
        try {
            return $this->countAcademicYearAndEducationalTutorPersonQueryBuilder($academicYear, $person)
                ->andWhere('a.project = :project')
                ->setParameter('project', $project)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException|NonUniqueResultException) {
        }

        return 0;
    }

    public function findByStudentEnrollment(StudentEnrollment $studentEnrollment)
    {
        return $this->createQueryBuilder('ar')
            ->where('ar.studentEnrollment = :student_enrollment')
            ->setParameter('student_enrollment', $studentEnrollment)
            ->orderBy('ar.startDate')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromProjects($items)
    {
        $agreements = $this->createQueryBuilder('a')
            ->where('a.project IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();

        return $this->deleteFromList($agreements);
    }
}
