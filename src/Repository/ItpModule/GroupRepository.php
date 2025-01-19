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
use App\Entity\Edu\Group;
use App\Entity\ItpModule\ProgramGroup;
use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class GroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Group::class);
    }

    public function findByAcademicYear(AcademicYear $academicYear): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->distinct(true)
            ->from(Group::class, 'g')
            ->join(ProgramGroup::class, 'pg', 'WITH', 'g = pg.group')
            ->join('pg.programGrade', 'pgg')
            ->join('pgg.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->where('tr.academicYear = :academic_year')
            ->setParameter('academic_year', $academicYear)
            ->addOrderBy('g.name')
            ->getQuery()
            ->getResult();
    }

    private function findByAcademicYearAndItpTeacherPersonQueryBuilder(AcademicYear $academicYear, Person $person): QueryBuilder
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->join(ProgramGroup::class, 'pg', 'WITH', 'g = pg.group')
            ->leftJoin('pg.managers', 'm')
            ->leftJoin('g.tutors', 'tu')
            ->leftJoin('pg.studentPrograms', 'sp')
            ->leftJoin('sp.studentProgramWorkcenters', 'spw')
            ->leftJoin('spw.educationalTutor', 'et')
            ->leftJoin('spw.additionalEducationalTutor', 'aet')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->leftJoin('tr.department', 'd')
            ->leftJoin('d.head', 'he')
            ->where('tr.academicYear = :academic_year AND (m.person = :person OR tu.person = :person OR he.person = :person OR et.person = :person OR aet.person = :person)')
            ->setParameter('academic_year', $academicYear)
            ->setParameter('person', $person)
            ->addOrderBy('g.name');
    }

    public function findByAcademicYearAndItpTeacherPerson(AcademicYear $academicYear, Person $person): array
    {
        return $this->findByAcademicYearAndItpTeacherPersonQueryBuilder($academicYear, $person)
            ->distinct(true)
            ->getQuery()
            ->getResult();
    }

    public function countAcademicYearAndItpTeacherPerson(AcademicYear $academicYear, Person $person): array
    {
        return $this->findByAcademicYearAndItpTeacherPersonQueryBuilder($academicYear, $person)
            ->select('COUNT(DISTINCT g)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
