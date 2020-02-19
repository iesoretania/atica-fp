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

namespace AppBundle\Entity\WLT;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="wlt_work_day",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"agreement_id", "date"})}))))
 */
class WorkDay
{
    const NO_ABSENCE = 0;
    const UNJUSTIFIED_ABSENCE = 1;
    const JUSTIFIED_ABSENCE = 2;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Agreement", inversedBy="workDays")
     * @ORM\JoinColumn(nullable=false)
     * @var Agreement
     */
    private $agreement;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $hours;

    /**
     * @ORM\Column(type="date")
     * @var \DateTime
     */
    private $date;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $notes;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $locked;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $absence;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Regex("/^\d\d:\d\d$/")
     * @var string
     */
    private $startTime1;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Regex("/^\d\d:\d\d$/")
     * @var string
     */
    private $endTime1;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Regex("/^\d\d:\d\d$/")
     * @var string
     */
    private $startTime2;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Regex("/^\d\d:\d\d$/")
     * @var string
     */
    private $endTime2;

    /**
     * @ORM\ManyToMany(targetEntity="ActivityRealization")
     * @ORM\JoinTable("wlt_tracking")
     * @var ActivityRealization[]
     */
    private $activityRealizations;

    public function __construct()
    {
        $this->locked = false;
        $this->absence = false;
        $this->activityRealizations = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Agreement
     */
    public function getAgreement()
    {
        return $this->agreement;
    }

    /**
     * @param Agreement $agreement
     * @return WorkDay
     */
    public function setAgreement(Agreement $agreement)
    {
        $this->agreement = $agreement;
        return $this;
    }

    /**
     * @return int
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * @param int $hours
     * @return WorkDay
     */
    public function setHours($hours)
    {
        $this->hours = $hours;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return WorkDay
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     * @return WorkDay
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked()
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     * @return WorkDay
     */
    public function setLocked($locked)
    {
        $this->locked = $locked;
        return $this;
    }

    /**
     * @return int
     */
    public function getAbsence()
    {
        return $this->absence;
    }

    /**
     * @param int $absence
     * @return WorkDay
     */
    public function setAbsence($absence)
    {
        $this->absence = $absence;
        return $this;
    }

    /**
     * @return string
     */
    public function getStartTime1()
    {
        return $this->startTime1;
    }

    /**
     * @param string $startTime1
     * @return WorkDay
     */
    public function setStartTime1($startTime1)
    {
        $this->startTime1 = $startTime1;
        return $this;
    }

    /**
     * @return string
     */
    public function getEndTime1()
    {
        return $this->endTime1;
    }

    /**
     * @param string $endTime1
     * @return WorkDay
     */
    public function setEndTime1($endTime1)
    {
        $this->endTime1 = $endTime1;
        return $this;
    }

    /**
     * @return string
     */
    public function getStartTime2()
    {
        return $this->startTime2;
    }

    /**
     * @param string $startTime2
     * @return WorkDay
     */
    public function setStartTime2($startTime2)
    {
        $this->startTime2 = $startTime2;
        return $this;
    }

    /**
     * @return string
     */
    public function getEndTime2()
    {
        return $this->endTime2;
    }

    /**
     * @param string $endTime2
     * @return WorkDay
     */
    public function setEndTime2($endTime2)
    {
        $this->endTime2 = $endTime2;
        return $this;
    }

    /**
     * @return ActivityRealization[]|Collection
     */
    public function getActivityRealizations()
    {
        return $this->activityRealizations;
    }

    /**
     * @param ActivityRealization[] $activityRealizations
     * @return WorkDay
     */
    public function setActivityRealizations($activityRealizations)
    {
        $this->activityRealizations = $activityRealizations;
        return $this;
    }
}
