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

use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\Teacher;
use App\Entity\WLT\Contact;
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

    public function getTeacherStatsByIdAndFilterQueryBuilder($teachers, $q)
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

    public function findByTeacherWorkcenterProjectsAndMethods(
        Teacher $teacher,
        Workcenter $workcenter = null,
        $projects = [],
        $contactMethods = []
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

        if ($workcenter) {
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

    public function getContactMethodStatsByTeacherWorkcenterProjectsAndMethods(
        Teacher $teacher,
        Workcenter $workcenter = null,
        $projects = [],
        $contactMethods = []
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

        if ($workcenter) {
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
}
