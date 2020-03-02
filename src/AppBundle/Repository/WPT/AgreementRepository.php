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

namespace AppBundle\Repository\WPT;

use AppBundle\Entity\WPT\Agreement;
use AppBundle\Entity\WPT\Shift;
use AppBundle\Entity\WPT\WorkDay;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class AgreementRepository extends ServiceEntityRepository
{
    private $workDayRepository;

    public function __construct(
        ManagerRegistry $registry,
        WorkDayRepository $workDayRepository
    ) {
        parent::__construct($registry, Agreement::class);
        $this->workDayRepository = $workDayRepository;
    }

    public function findByShiftQueryBuilder(
        Shift $shift
    ) {
        return $this->createQueryBuilder('a')
            ->join('a.studentEnrollment', 'se')
            ->join('se.group', 'g')
            ->join('se.person', 'p')
            ->join('g.grade', 'gr')
            ->join('gr.training', 'tr')
            ->join('tr.academicYear', 'ay')
            ->where('a.shift = :shift')
            ->setParameter('shift', $shift)
            ->orderBy('g.name')
            ->addOrderBy('p.lastName')
            ->addOrderBy('p.firstName');
    }

    /**
     * @return Agreement[]
     */
    public function findAllInListByIdAndShift(
        $items,
        Shift $shift
    ) {
        return $this->findByShiftQueryBuilder($shift)
            ->andWhere('a.id IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Agreement[]
     */
    public function findAllInListByNotIdAndShift(
        $items,
        Shift $shift
    ) {
        return $this->findByShiftQueryBuilder($shift)
            ->andWhere('a.id NOT IN (:items)')
            ->setParameter('items', $items)
            ->getQuery()
            ->getResult();
    }

    public function deleteFromList($list)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->delete(Agreement::class, 'a')
            ->where('a IN (:list)')
            ->setParameter('list', $list)
            ->getQuery()
            ->execute();
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

        /** @var WorkDay $workDay */
        foreach ($workDays as $workDay) {
            $newDate = clone $workDay->getDate();
            $newWorkDay = $this->workDayRepository->findOneByAgreementAndDate($destination, $newDate);
            if (null === $newWorkDay) {
                $newWorkDay = new WorkDay();
                $newWorkDay
                    ->setAgreement($destination)
                    ->setDate($newDate)
                    ->setHours($workDay->getHours());
                $this->getEntityManager()->persist($newWorkDay);
            } elseif ($overwrite) {
                $newWorkDay->setHours($workDay->getHours());
            } else {
                $newWorkDay->setHours($newWorkDay->getHours() + $workDay->getHours());
            }
        }
    }
}