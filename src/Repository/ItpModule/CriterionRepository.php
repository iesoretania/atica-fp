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

use App\Entity\ItpModule\Activity;
use App\Entity\ItpModule\StudentProgramWorkcenter;
use App\Entity\ItpModule\StudentProgramWorkcenterActivity;
use App\Repository\Edu\CriterionRepository as EduCriterionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ManagerRegistry;

class CriterionRepository extends EduCriterionRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry);
    }

    final public function getStudentProgramWorkcenterStats(StudentProgramWorkcenter $studentProgramWorkcenter): array
    {
        $activities = $studentProgramWorkcenter->getActivities();
        $criteria = new ArrayCollection();
        foreach ($activities as $activity) {
            foreach ($activity->getActivity()?->getCriteria() as $criterion) {
                if (!$criteria->contains($criterion)) {
                    $criteria->add($criterion);
                }
            }
        }

        return $this->createQueryBuilder('c')
            ->addSelect('AVG(sv.numericGrade)')
            ->leftJoin(Activity::class, 'a', 'WITH', 'c MEMBER OF a.criteria')
            ->leftJoin(StudentProgramWorkcenterActivity::class, 'spwa', 'WITH', 'spwa.activity = a AND spwa.studentProgramWorkcenter = :studentProgramWorkcenter')
            ->leftJoin('spwa.scaleValue', 'sv')
            ->join('c.learningOutcome', 'lo')
            ->join('lo.subject', 's')
            ->where('c IN (:criteria)')
            ->setParameter('criteria', $criteria)
            ->groupBy('c')
            ->orderBy('s.name', 'ASC')
            ->addOrderBy('lo.code', 'ASC')
            ->addOrderBy('c.code', 'ASC')
            ->setParameter('studentProgramWorkcenter', $studentProgramWorkcenter)
            ->getQuery()
            ->getResult();
    }
}
