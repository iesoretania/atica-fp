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
use App\Entity\Edu\Teaching;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\WLT\Agreement;
use App\Entity\WLT\EducationalTutorAnsweredSurvey;
use App\Entity\WLT\Project;
use App\Repository\Edu\TeacherRepository;
use App\Security\OrganizationVoter;
use App\Security\WLT\WLTOrganizationVoter;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Security;

class WLTTeacherRepository extends TeacherRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly Security $security,
        private readonly ProjectRepository $projectRepository
    ) {
        parent::__construct($registry);
    }

    public function findByAcademicYear(AcademicYear $academicYear)
    {
        return $this->createQueryBuilder('t')
            ->join('t.person', 'p')
            ->join(Teaching::class, 'te', 'WITH', 'te.teacher = t')
            ->join('te.group', 'g')
            ->join(Project::class, 'pr', 'WITH', 'g MEMBER OF pr.groups')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function findByGroupsOrEducationalTutor($groups, AcademicYear $academicYear)
    {
        /**
         * @var Collection|Teacher[]
         */
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
                Agreement::class,
                'a',
                'WITH',
                'a.educationalTutor = t OR a.additionalEducationalTutor = t'
            )
            ->join(StudentEnrollment::class, 'se')
            ->andWhere('se.group IN (:groups)')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('groups', $groups)
            ->setParameter('academic_year', $academicYear)
            ->getQuery()
            ->getResult();

        foreach ($educationalTutors as $educationalTutor) {
            if (!in_array($educationalTutor, $teachers, false)) {
                array_unshift($teachers, $educationalTutor);
            }
        }

        return $teachers;
    }

    public function findOneByOrganizationAndId(Organization $organization, $id)
    {
        return $this->createQueryBuilder('t')
            ->join('t.person', 'p')
            ->join(Teaching::class, 'te', 'WITH', 'te.teacher = t')
            ->join('te.group', 'g')
            ->join(Project::class, 'pr', 'WITH', 'g MEMBER OF pr.groups')
            ->join('t.academicYear', 'ay')
            ->andWhere('ay.organization = :organization')
            ->andWhere('t.id = :id')
            ->setParameter('organization', $organization)
            ->setParameter('id', $id)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByProject(Project $project)
    {
        return $this->createQueryBuilder('t')
            ->join('t.person', 'p')
            ->join(Teaching::class, 'te', 'WITH', 'te.teacher = t')
            ->join('te.group', 'g')
            ->join(Project::class, 'pr', 'WITH', 'g MEMBER OF pr.groups')
            ->where('pr = :project')
            ->setParameter('project', $project)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->getQuery()
            ->getResult();
    }

    public function getStatsByProjectAndAcademicYearWithAnsweredSurvey(
        Project $project,
        ?AcademicYear $academicYear
    ) {
        $qb = $this->createQueryBuilder('t')
            ->select('t')
            ->addSelect('etas')
            ->join('t.person', 'p')
            ->join(
                Agreement::class,
                'a',
                'WITH',
                'a.educationalTutor = t OR a.additionalEducationalTutor = t'
            )
            ->join(Project::class, 'pr', 'WITH', 'a.project = pr')
            ->leftJoin(
                EducationalTutorAnsweredSurvey::class,
                'etas',
                'WITH',
                'etas.teacher = t AND etas.project = pr'
            )
            ->where('pr = :project')
            ->setParameter('project', $project);

        if ($academicYear) {
            $qb
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }
        $data = $qb
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->groupBy('t, pr, etas')
            ->getQuery()
            ->getResult();

        return array_chunk($data, 2);
    }

    public function findTeachersDataByProjectGroupByProjectAndPersonFilteredQueryBuilder(
        $q,
        AcademicYear $academicYear,
        Person $person
    ) {
        $organization = $academicYear->getOrganization();

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('t.id AS teacherId')
            ->addSelect('p.firstName AS firstName')
            ->addSelect('p.lastName AS lastName')
            ->addSelect('pro.id AS projectId')
            ->addSelect('pro.name AS projectName')
            ->addSelect('pro.locked AS projectLocked')
            ->addSelect('ay.id AS academicYearId')
            ->addSelect('ay.description AS academicYearDescription')
            ->addSelect('COUNT(etas)')
            ->from(Teacher::class, 't')
            ->join(Agreement::class, 'a', 'WITH', 'a.educationalTutor = t OR a.additionalEducationalTutor = t')
            ->join(Project::class, 'pro', 'WITH', 'a.project = pro')
            ->join('t.person', 'p')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->leftJoin(
                EducationalTutorAnsweredSurvey::class,
                'etas',
                'WITH',
                'etas.teacher = t AND etas.project = pro'
            )
            ->addGroupBy('t, pro')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName')
            ->addOrderBy('pro.name');

        if ($q) {
            $queryBuilder
                ->orWhere('pro.name LIKE :tq')
                ->orWhere('p.lastName LIKE :tq')
                ->orWhere('p.firstName LIKE :tq')
                ->setParameter('tq', '%'.$q.'%');
        }

        $isManager = $this->security->isGranted(OrganizationVoter::MANAGE, $organization);
        $isWltManager = $this->security->isGranted(WLTOrganizationVoter::WLT_MANAGER, $organization);

        if (!$isManager && $isWltManager) {
            $projects = $this->projectRepository->findByManager($person);
            $queryBuilder
                ->andWhere('pro IN (:projects) OR p = :person')
                ->setParameter('projects', $projects)
                ->setParameter('person', $person);
        }

        if (!$isWltManager && !$isManager) {
            $queryBuilder
                ->andWhere('p = :person')
                ->setParameter('person', $person);
        }

        $queryBuilder
            ->andWhere('tr.academicYear = :academic_year')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear);

        return $queryBuilder;
    }

    public function findByProjectAndAcademicYear(Project $project, ?AcademicYear $academicYear = null)
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.person', 'p')
            ->join(Teaching::class, 'te', 'WITH', 'te.teacher = t')
            ->join('te.group', 'g')
            ->join(Project::class, 'pr', 'WITH', 'g MEMBER OF pr.groups')
            ->where('pr = :project')
            ->setParameter('project', $project)
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if ($academicYear) {
            $qb
                ->andWhere('t.academicYear = :academic_year')
                ->setParameter('academic_year', $academicYear);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}
