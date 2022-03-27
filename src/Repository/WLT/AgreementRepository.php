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

namespace App\Repository\WLT;

use App\Entity\Edu\AcademicYear;
use App\Entity\Edu\Teacher;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\WLT\Agreement;
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
    private $workDayRepository;
    private $WLTGroupRepository;
    private $projectRepository;
    private $security;

    public function __construct(
        ManagerRegistry $registry,
        WorkDayRepository $workDayRepository,
        WLTGroupRepository $WLTGroupRepository,
        ProjectRepository $projectRepository,
        Security $security
    ) {
        parent::__construct($registry, Agreement::class);
        $this->workDayRepository = $workDayRepository;
        $this->WLTGroupRepository = $WLTGroupRepository;
        $this->projectRepository = $projectRepository;
        $this->security = $security;
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $student
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

    /**
     * @param AcademicYear $academicYear
     * @param Person $student
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
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return 0;
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $workTutor
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
                ->where('a.workTutor = :work_tutor')
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('work_tutor', $workTutor)
                ->setParameter('academic_year', $academicYear)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return 0;
    }

    /**
     * @param AcademicYear $academicYear
     * @param Person $person
     *
     * @return int
     */
    public function countAcademicYearAndEducationalTutorPerson(AcademicYear $academicYear, Person $person)
    {
        try {
            return $this->createQueryBuilder('a')
                ->select('COUNT(a)')
                ->join('a.educationalTutor', 't')
                ->andWhere('t.academicYear = :academic_year')
                ->andWhere('t.person = :educational_tutor')
                ->setParameter('educational_tutor', $person)
                ->setParameter('academic_year', $academicYear)
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException $e) {
        } catch (NonUniqueResultException $e) {
        }

        return 0;
    }

    /**
     * @param $items
     * @param Project $project
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
     * @param Project $project
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

    public function updateDates(Agreement $agreement)
    {
        $workDays = $this->workDayRepository->findByAgreement($agreement);
        if (count($workDays) === 0) {
            return;
        }
        /** @var WorkDay $first */
        $first = $workDays[0];
        /** @var WorkDay $last */
        $last = $workDays[count($workDays) - 1];
        $agreement
            ->setStartDate($first->getDate())
            ->setEndDate($last->getDate());

        $this->getEntityManager()->flush();
    }

    public function cloneCalendarFromAgreement(Agreement $destination, Agreement $source, $overwrite = false)
    {
        $workDays = $this->workDayRepository->findByAgreement($source);
        if (count($workDays) === 0) {
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
     * @return bool
     */
    public function deleteFromList($list)
    {
        $em = $this->getEntityManager();
        /** @var Agreement $agreement */
        foreach ($list as $agreement) {
            if ($agreement->getCompanySurvey()) {
                $answers = $agreement->getCompanySurvey()->getAnswers();
                foreach ($answers as $answer) {
                    $em->remove($answer);
                }
                $em->remove($agreement->getCompanySurvey());
            }

            if ($agreement->getStudentSurvey()) {
                $answers = $agreement->getStudentSurvey()->getAnswers();
                foreach ($answers as $answer) {
                    $em->remove($answer);
                }
                $em->remove($agreement->getStudentSurvey());
            }

            $evaluatedActivityRealizations = $agreement->getEvaluatedActivityRealizations();
            foreach ($evaluatedActivityRealizations as $evaluatedActivityRealization) {
                $em->remove($evaluatedActivityRealization);
            }

            $workDays = $agreement->getWorkDays();
            foreach ($workDays as $workDay) {
                $em->remove($workDay);
            }

            $em->remove($agreement);
        }

        return true;
    }

    public function meetingStatsByTeacher(Teacher $teacher)
    {
        return $this->createQueryBuilder('a')
            ->select('a')
            ->addSelect('se')
            ->addSelect('p')
            ->addSelect(
                'SUM(CASE WHEN DATE(m.dateTime) >= a.startDate AND DATE(m.dateTime) <= a.endDate THEN 1 ELSE 0 END)'
            )
            ->addSelect('COUNT(m.dateTime)')
            ->join('a.studentEnrollment', 'se')
            ->join('se.person', 'p')
            ->leftJoin(Meeting::class, 'm', 'WITH', 'se MEMBER OF m.studentEnrollments')
            ->leftJoin(Teacher::class, 't', 'WITH', 't MEMBER OF m.teachers')
            ->groupBy('a')
            ->andWhere('t = :teacher')
            ->setParameter('teacher', $teacher)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->getQuery()
            ->getResult();
    }

    public function attendanceStatsByProject(Project $project)
    {
        return $this->createQueryBuilder('a')
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
            ->groupBy('a')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('a.startDate')
            ->addOrderBy('c.name')
            ->where('a.project = :project')
            ->setParameter('project', $project)
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

        if (false === $isManager && false === $isWltManager) {
            // no es administrador ni coordinador de FP:
            // puede ser jefe de departamento, tutor de grupo o profesor
            $groups =
                $this->WLTGroupRepository->findByOrganizationAndPerson($organization, $person);

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

        if ($project) {
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

        if ($project) {
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
}
