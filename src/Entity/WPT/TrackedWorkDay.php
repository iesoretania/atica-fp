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

namespace App\Entity\WPT;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WPT\TrackedWorkDayRepository")
 * @ORM\Table(name="wpt_tracked_work_day",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"agreement_enrollment_id", "work_day_id"})}))))
 */
class TrackedWorkDay
{
    public const NO_ABSENCE = 0;
    public const UNJUSTIFIED_ABSENCE = 1;
    public const JUSTIFIED_ABSENCE = 2;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="WorkDay")
     * @ORM\JoinColumn(nullable=false)
     * @var WorkDay
     */
    private $workDay;

    /**
     * @ORM\ManyToOne(targetEntity="AgreementEnrollment", inversedBy="trackedWorkDays")
     * @ORM\JoinColumn(nullable=false)
     * @var AgreementEnrollment
     */
    private $agreementEnrollment;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $notes;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $otherActivities;

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
     * @ORM\OneToMany(targetEntity="ActivityTracking", mappedBy="trackedWorkDay")
     * @var ActivityTracking[]|Collection
     */
    private $trackedActivities;

    public function __construct()
    {
        $this->locked = false;
        $this->absence = self::NO_ABSENCE;
        $this->trackedActivities = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return WorkDay
     */
    public function getWorkDay()
    {
        return $this->workDay;
    }

    /**
     * @param WorkDay $workDay
     * @return TrackedWorkDay
     */
    public function setWorkDay($workDay)
    {
        $this->workDay = $workDay;
        return $this;
    }

    /**
     * @return AgreementEnrollment
     */
    public function getAgreementEnrollment()
    {
        return $this->agreementEnrollment;
    }

    /**
     * @param AgreementEnrollment $agreementEnrollment
     * @return TrackedWorkDay
     */
    public function setAgreementEnrollment($agreementEnrollment)
    {
        $this->agreementEnrollment = $agreementEnrollment;
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
     * @return TrackedWorkDay
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return string
     */
    public function getOtherActivities()
    {
        return $this->otherActivities;
    }

    /**
     * @param string $otherActivities
     * @return TrackedWorkDay
     */
    public function setOtherActivities($otherActivities)
    {
        $this->otherActivities = $otherActivities;
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
     * @return TrackedWorkDay
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
     * @return TrackedWorkDay
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
     * @return TrackedWorkDay
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
     * @return TrackedWorkDay
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
     * @return TrackedWorkDay
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
     * @return TrackedWorkDay
     */
    public function setEndTime2($endTime2)
    {
        $this->endTime2 = $endTime2;
        return $this;
    }

    /**
     * @return ActivityTracking[]|Collection
     */
    public function getTrackedActivities()
    {
        return $this->trackedActivities;
    }

    /**
     * @param ActivityTracking[]|Collection $trackedActivities
     * @return TrackedWorkDay
     */
    public function setTrackedActivities($trackedActivities)
    {
        $this->trackedActivities = $trackedActivities;
        return $this;
    }
}
