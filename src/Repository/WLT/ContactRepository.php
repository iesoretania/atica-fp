<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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
use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\Teacher;
use App\Entity\WLT\Contact;
use App\Entity\WLT\Project;
use App\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findAllInListById($items)
    {
        return $this->createQueryBuilder('v')
            ->where('v IN (:items)')
            ->join('v.workcenter', 'w')
            ->setParameter('items', $items)
            ->orderBy('v.dateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param Contact[]
     * @return mixed
     */
    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Contact::class, 'v')
            ->where('v IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
    }

    public function getTeacherStatsByIdAndFilterQueryBuilder($teachers, ?string $q)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->from(Teacher::class, 't')
            ->select('t, COUNT(c), COUNT(c.method)')
            ->leftJoin(Contact::class, 'c', 'WITH', 'c.teacher = t')
            ->leftJoin('t.person', 'p')
            ->groupBy('t')
            ->orderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if ($q) {
            $qb
                ->where('p.lastName LIKE :tq OR p.firstName LIKE :tq')
                ->setParameter('tq', '%'. $q . '%');
        }
        $qb
            ->andWhere('t IN (:items)')
            ->setParameter('items', $teachers);

        return $qb;
    }

    public function getWorkcenterStatsByIdAcademicYearAndFilterQueryBuilder($workcenters, $academicYear, ?string $q)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->from(Workcenter::class, 'w')
            ->select('w, COUNT(c), COUNT(c.method)')
            ->leftJoin(Contact::class, 'c', 'WITH', 'c.workcenter = w')
            ->leftJoin('w.company', 'co')
            ->leftJoin('c.teacher', 't')
            ->andWhere('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->groupBy('w')
            ->orderBy('co.name')
            ->addOrderBy('w.name');

        if ($q) {
            $qb
                ->where('co.name LIKE :tq OR w.name LIKE :tq')
                ->setParameter('tq', '%'. $q . '%');
        }
        $qb
            ->andWhere('w IN (:items)')
            ->setParameter('items', $workcenters);

        return $qb;
    }

    public function findWorkcentersByTeacher(Teacher $teacher)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('w')
            ->from(Workcenter::class, 'w')
            ->join(Contact::class, 'c', 'WITH', 'c.workcenter = w AND c.teacher = :teacher')
            ->join('w.company', 'co')
            ->setParameter('teacher', $teacher)
            ->orderBy('co.name')
            ->addOrderBy('w.name')
            ->getQuery()
            ->getResult();
    }

    public function findWorkcentersByTeachers($teachers)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('w')
            ->from(Workcenter::class, 'w')
            ->join(Contact::class, 'c', 'WITH', 'c.workcenter = w AND c.teacher IN (:teachers)')
            ->join('w.company', 'co')
            ->join('c.teacher', 't')
            ->setParameter('teachers', $teachers)
            ->orderBy('co.name')
            ->addOrderBy('w.name')
            ->getQuery()
            ->getResult();
    }

    public function findByTeacherWorkcenterProjectsAndMethods(
        Teacher $teacher,
        Workcenter $workcenter = null,
        array $projects = [],
        array $contactMethods = []
    ) {
        $qb = $this->createQueryBuilder('c')
            ->select('c, p, w, t, m, pe')
            ->join('c.teacher', 't')
            ->join('t.person', 'pe')
            ->leftJoin('c.method', 'm')
            ->leftJoin('c.projects', 'p')
            ->leftJoin('c.workcenter', 'w')
            ->where('c.teacher = :teacher')
            ->setParameter('teacher', $teacher);

        if ($workcenter instanceof Workcenter) {
            $qb
                ->andWhere('c.workcenter = :workcenter')
                ->setParameter('workcenter', $workcenter);
        }

        if ($projects !== []) {
            $noProject = false;
            if (($key = array_search(null, $projects, true)) !== false) {
                unset($projects[$key]);
                $noProject = true;
            }
            $qb
                ->andWhere('p IN (:projects)' . ($noProject ? ' OR p IS NULL' : ''))
                ->setParameter('projects', $projects);
        }

        if ($contactMethods !== []) {
            $onsite = false;
            if (($key = array_search(null, $contactMethods, true)) !== false) {
                unset($contactMethods[$key]);
                $onsite = true;
            }

            $qb
                ->andWhere('m IN (:contact_methods)' . ($onsite ? ' OR m IS NULL' : ''))
                ->setParameter('contact_methods', $contactMethods);
        }

        return $qb
            ->orderBy('c.dateTime')
            ->getQuery()
            ->getResult();
    }

    public function findByAcademicYearWorkcenterProjectsAndMethods(
        AcademicYear $academicYear,
        Workcenter $workcenter,
        array $projects = [],
        array $contactMethods = []
    ) {
        $qb = $this->createQueryBuilder('c')
            ->select('c, p, w, t, m, pe')
            ->join('c.teacher', 't')
            ->join('t.person', 'pe')
            ->leftJoin('c.method', 'm')
            ->leftJoin('c.projects', 'p')
            ->leftJoin('c.workcenter', 'w')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->andWhere('c.workcenter = :workcenter')
            ->setParameter('workcenter', $workcenter);

        if ($projects !== []) {
            $noProject = false;
            if (($key = array_search(null, $projects, true)) !== false) {
                unset($projects[$key]);
                $noProject = true;
            }
            $qb
                ->andWhere('p IN (:projects)' . ($noProject ? ' OR p IS NULL' : ''))
                ->setParameter('projects', $projects);
        }

        if ($contactMethods !== []) {
            $onsite = false;
            if (($key = array_search(null, $contactMethods, true)) !== false) {
                unset($contactMethods[$key]);
                $onsite = true;
            }

            $qb
                ->andWhere('m IN (:contact_methods)' . ($onsite ? ' OR m IS NULL' : ''))
                ->setParameter('contact_methods', $contactMethods);
        }

        return $qb
            ->orderBy('c.dateTime')
            ->getQuery()
            ->getResult();
    }

    public function getContactMethodStatsByTeacherWorkcenterProjectsAndMethods(
        Teacher $teacher,
        Workcenter $workcenter = null,
        array $projects = [],
        array $contactMethods = []
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('cm, COUNT(c)')
            ->from(ContactMethod::class, 'cm')
            ->join(Contact::class, 'c', 'WITH', 'c.method = cm AND c.teacher = :teacher')
            ->join('c.teacher', 't')
            ->join('t.person', 'pe')
            ->leftJoin('c.method', 'm')
            ->leftJoin('c.projects', 'p')
            ->leftJoin('c.workcenter', 'w')
            ->groupBy('cm')
            ->where('cm.enabled = true AND cm.academicYear = :academic_year')
            ->setParameter('academic_year', $teacher->getAcademicYear())
            ->setParameter('teacher', $teacher);

        if ($workcenter instanceof Workcenter) {
            $qb
                ->andWhere('c.workcenter = :workcenter')
                ->setParameter('workcenter', $workcenter);
        }

        if ($projects !== []) {
            $noProject = false;
            if (($key = array_search(null, $projects, true)) !== false) {
                unset($projects[$key]);
                $noProject = true;
            }
            $qb
                ->andWhere('p IN (:projects)' . ($noProject ? ' OR p IS NULL' : ''))
                ->setParameter('projects', $projects);
        }

        if ($contactMethods !== []) {
            $onsite = false;
            if (($key = array_search(null, $contactMethods, true)) !== false) {
                unset($contactMethods[$key]);
                $onsite = true;
            }

            $qb
                ->andWhere('m IN (:contact_methods)' . ($onsite ? ' OR m IS NULL' : ''))
                ->setParameter('contact_methods', $contactMethods);
        }

        return $qb
            ->orderBy('cm.description')
            ->getQuery()
            ->getResult();
    }

    public function getContactMethodStatsByAcademicYearWorkcenterProjectsAndMethods(
        AcademicYear $academicYear,
        Workcenter $workcenter = null,
        array $projects = [],
        array $contactMethods = []
    ) {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('cm, COUNT(c)')
            ->from(ContactMethod::class, 'cm')
            ->join(Contact::class, 'c', 'WITH', 'c.method = cm')
            ->leftJoin('c.method', 'm')
            ->leftJoin('c.projects', 'p')
            ->leftJoin('c.workcenter', 'w')
            ->leftJoin('c.teacher', 't')
            ->groupBy('cm')
            ->where('cm.enabled = true AND cm.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->andWhere('c.workcenter = :workcenter AND t.academicYear = :academic_year')
            ->setParameter('workcenter', $workcenter);

        if ($projects !== []) {
            $noProject = false;
            if (($key = array_search(null, $projects, true)) !== false) {
                unset($projects[$key]);
                $noProject = true;
            }
            $qb
                ->andWhere('p IN (:projects)' . ($noProject ? ' OR p IS NULL' : ''))
                ->setParameter('projects', $projects);
        }

        if ($contactMethods !== []) {
            $onsite = false;
            if (($key = array_search(null, $contactMethods, true)) !== false) {
                unset($contactMethods[$key]);
                $onsite = true;
            }

            $qb
                ->andWhere('m IN (:contact_methods)' . ($onsite ? ' OR m IS NULL' : ''))
                ->setParameter('contact_methods', $contactMethods);
        }

        return $qb
            ->orderBy('cm.description')
            ->getQuery()
            ->getResult();
    }

    public function findWorkcentersByTeacherAndProjects(Teacher $teacher, array $projects)
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('w')
            ->from(Workcenter::class, 'w')
            ->join(Contact::class, 'c', 'WITH', 'c.workcenter = w AND c.teacher = :teacher')
            ->leftJoin('c.projects', 'p')
            ->join('w.company', 'co')
            ->setParameter('teacher', $teacher)
            ->orderBy('co.name')
            ->addOrderBy('w.name');

        if ($projects !== []) {
            $noProject = false;
            if (($key = array_search(null, $projects, true)) !== false) {
                unset($projects[$key]);
                $noProject = true;
            }
            $qb
                ->andWhere('p IN (:projects)' . ($noProject ? ' OR p IS NULL' : ''))
                ->setParameter('projects', $projects);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }

    public function deleteFromProjects($items): void
    {
        $contacts = $this->createQueryBuilder('c')
            ->join('c.projects', 'p')
            ->where('p IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
        /** @var Project $item */
        foreach ($items as $item) {
            /** @var Contact $contact */
            foreach ($contacts as $contact) {
                if ($contact->getProjects()->contains($item)) {
                    $contact->getProjects()->removeElement($item);
                }
            }
        }
    }
}
