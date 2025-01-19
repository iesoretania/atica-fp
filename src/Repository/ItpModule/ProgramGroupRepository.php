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

use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\ItpModule\ProgramGrade;
use App\Entity\ItpModule\ProgramGroup;
use App\Repository\Edu\GroupRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class ProgramGroupRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly GroupRepository $groupRepository
    ) {
        parent::__construct($registry, ProgramGroup::class);
    }

    public function findOrCreateAllByProgramGrade(ProgramGrade $programGrade): array
    {
        do {
            $changes = false;
            $actualProgramGroups = $this->createQueryBuilder('pg')
                ->join('pg.group', 'g')
                ->where('pg.programGrade = :program_grade')
                ->setParameter('program_grade', $programGrade)
                ->orderBy('g.name', 'ASC')
                ->getQuery()
                ->getResult();

            $groups = $this->groupRepository->findByGrade($programGrade->getGrade());

            foreach ($actualProgramGroups as $actualProgramGroup) {
                if (!in_array($actualProgramGroup->getGroup(), $groups)) {
                    $changes = true;
                    $this->getEntityManager()->remove($actualProgramGroup);
                }
            }

            $return = [];
            foreach ($groups as $group) {
                $found = false;
                foreach ($actualProgramGroups as $actualProgramGroup) {
                    if ($actualProgramGroup->getGroup() === $group) {
                        $return[] = $actualProgramGroup;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $changes = true;
                    $programGroup = new ProgramGroup();
                    $programGroup
                        ->setProgramGrade($programGrade)
                        ->setGroup($group);
                    if ($programGrade->getTargetHours()) {
                        $programGroup->setTargetHours($programGrade->getTargetHours());
                    }
                    $this->getEntityManager()->persist($programGroup);
                    $return[] = $programGroup;
                }
            }
            if ($changes) {
                $this->getEntityManager()->flush();
            }
        } while ($changes);

        return $return;
    }

    public function deleteFromProgramGradeList(array $items)
    {
        $this->createQueryBuilder('pg')
            ->delete()
            ->where('pg.programGrade IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->execute();
    }

    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    public function findByManager(Teacher $teacher): array
    {
        return $this->createQueryBuilder('pg')
            ->join('pg.managers', 'm')
            ->andWhere('m = :teacher')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    public function findByTutor(Teacher $teacher): array
    {
        return $this->createQueryBuilder('pg')
            ->join('pg.group', 'g')
            ->join('g.tutors', 't')
            ->andWhere('t = :teacher')
            ->setParameter('teacher', $teacher)
            ->getQuery()
            ->getResult();
    }

    public function persist(ProgramGroup $programGroup): void
    {
        $this->getEntityManager()->persist($programGroup);
    }

    public function getStudentEnrollmentWorkDaysStats(ProgramGroup $programGroup): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->from(StudentEnrollment::class, 'se')
            ->select('se.id', 'SUM(wd.hours) AS total_hours')
            ->addSelect('MIN(wd.date) AS min_start_date', 'MAX(wd.date) AS max_end_date')
            ->distinct()
            ->join('se.group', 'g')
            ->join(ProgramGroup::class, 'pg', 'WITH', 'pg.group = g')
            ->join('pg.studentPrograms', 'sp', Join::WITH, 'sp.studentEnrollment = se')
            ->leftJoin('sp.studentProgramWorkcenters', 'spw')
            ->leftJoin('spw.workDays', 'wd')
            ->where('pg = :program_group')
            ->setParameter('program_group', $programGroup)
            ->groupBy('se.id')
            ->getQuery()
            ->getResult();
    }

    public function getStudentEnrollmentActivitiesStats(ProgramGroup $programGroup): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->from(StudentEnrollment::class, 'se')
            ->select('se.id', 'COUNT(DISTINCT ac) AS total_activities')
            ->addSelect('SUM(CASE WHEN a.scaleValue IS NOT NULL THEN 1 ELSE 0 END) AS graded_activities')
            ->distinct()
            ->join('se.group', 'g')
            ->join(ProgramGroup::class, 'pg', 'WITH', 'pg.group = g')
            ->join('pg.studentPrograms', 'sp', Join::WITH, 'sp.studentEnrollment = se')
            ->leftJoin('sp.studentProgramWorkcenters', 'spw')
            ->leftJoin('spw.activities', 'a')
            ->leftJoin('a.activity', 'ac')
            ->where('pg = :program_group')
            ->setParameter('program_group', $programGroup)
            ->groupBy('se.id')
            ->getQuery()
            ->getResult();
    }
}
