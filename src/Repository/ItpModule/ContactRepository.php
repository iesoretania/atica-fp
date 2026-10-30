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
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\Contact;
use App\Entity\ItpModule\TrainingProgram;
use App\Entity\Person;
use App\Entity\Workcenter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findAllInListById(array $items): array
    {
        return $this->createQueryBuilder('c')
            ->where('c IN (:items)')
            ->join('c.workcenter', 'w')
            ->setParameter('items', $items)
            ->orderBy('c.dateTime', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList(array $list): void
    {
        $this->getEntityManager()->createQueryBuilder()
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
                ->andWhere('co.name LIKE :tq OR w.name LIKE :tq')
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

    public function deleteFromTrainingPrograms(array $items): void
    {
        $contacts = $this->createQueryBuilder('c')
            ->join('c.trainingPrograms', 'tp')
            ->where('tp IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
        foreach ($items as $item) {
            assert($item instanceof TrainingProgram);
            foreach ($contacts as $contact) {
                assert($contact instanceof Contact);
                if ($contact->getTrainingPrograms()->contains($item)) {
                    $contact->getTrainingPrograms()->removeElement($item);
                }
            }
        }
    }

    public function remove(Contact $contact): void
    {
        $this->getEntityManager()->remove($contact);
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function persist(Contact $contact): void
    {
        $this->getEntityManager()->persist($contact);
    }

    public function createContactQueryBuilder(
        AcademicYear $academicYear,
        Person $person,
        bool $isManager,
        array $methodCollection,
        ?string $q): QueryBuilder
    {
        $qb = $this->createQueryBuilder('c')
            ->select('c', 't', 'tp', 'p', 'se', 'sep', 'w', 'co')
            ->distinct()
            ->join('c.teacher', 't')
            ->join('t.person', 'p')
            ->leftJoin('c.workcenter', 'w')
            ->leftJoin('w.company', 'co')
            ->leftJoin('c.method', 'm')
            ->leftJoin('c.trainingPrograms', 'tp')
            ->leftJoin('tp.trainingProgramGrades', 'tpg')
            ->leftJoin('tpg.trainingProgramGroups', 'tpgg')
            ->leftJoin('tpgg.managers', 'tpggm')
            ->leftJoin('tpg.grade', 'gr')
            ->leftJoin('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'he')
            ->leftJoin('tpgg.group', 'g')
            ->leftJoin('g.tutors', 'tu')
            ->leftJoin('c.studentEnrollments', 'se')
            ->leftJoin('se.person', 'sep')
            ->leftJoin('se.group', 'seg')
            ->where('t.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->addOrderBy('c.dateTime', 'DESC')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName');

        if (!$isManager) {
            $qb
                ->andWhere('t.person = :person OR tpggm.person = :person OR he.person = :person OR tu.person = :person')
                ->setParameter('person', $person);
        }

        if ($methodCollection) {
            if (in_array(null, $methodCollection, true)) {
                $qb->andWhere('c.method IN (:methods) OR c.method IS NULL');
            } else {
                $qb->andWhere('c.method IN (:methods)');
            }
            $qb
                ->setParameter('methods', $methodCollection);
        }

        if ($q) {
            $qb
                ->andWhere('p.lastName LIKE :tq OR p.firstName LIKE :tq OR w.name LIKE :tq OR co.name LIKE :tq OR sep.lastName LIKE :tq OR sep.firstName LIKE :tq OR seg.name LIKE :tq')
                ->setParameter('tq', '%'. $q . '%');
        }

        return $qb;
    }
}
