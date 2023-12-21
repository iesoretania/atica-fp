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

use App\Entity\Workcenter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WPT\AgreementRepository")
 * @ORM\Table(name="wpt_agreement")
 */
class Agreement
{
    /**
     * @var ArrayCollection|Collection|Activity[]|mixed
     */
    public $activities;
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\WPT\Shift", inversedBy="agreements")
     * @ORM\JoinColumn(nullable=false)
     * @var Shift
     */
    private $shift;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Workcenter")
     * @ORM\JoinColumn(nullable=false)
     * @var Workcenter
     */
    private $workcenter;

    /**
     * @ORM\OneToMany(targetEntity="AgreementEnrollment", mappedBy="agreement")
     * @var AgreementEnrollment[]|Collection
     */
    private $agreementEnrollments;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $startDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $endDate;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $signDate;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Regex("/^\d\d:\d\d$/")
     * @var string
     */
    private $defaultStartTime1;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Regex("/^\d\d:\d\d$/")
     * @var string
     */
    private $defaultEndTime1;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Regex("/^\d\d:\d\d$/")
     * @var string
     */
    private $defaultStartTime2;

    /**
     * @ORM\Column(type="string", length=5, nullable=true)
     * @Assert\Regex("/^\d\d:\d\d$/")
     * @var string
     */
    private $defaultEndTime2;

    /**
     * @ORM\OneToMany(targetEntity="WorkDay", mappedBy="agreement")
     * @var WorkDay[]|Collection
     */
    private $workDays;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $locked;

    public function __construct()
    {
        $this->agreementEnrollments = new ArrayCollection();
        $this->workDays = new ArrayCollection();
        $this->activities = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getShift() . ' - ' . $this->getWorkcenter() . ($this->getName() !== '' && $this->getName() !== null ? ' - ' . $this->getName() : '');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Agreement
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Shift
     */
    public function getShift()
    {
        return $this->shift;
    }

    /**
     * @param Shift $shift
     * @return Agreement
     */
    public function setShift($shift)
    {
        $this->shift = $shift;
        return $this;
    }

    /**
     * @return Workcenter
     */
    public function getWorkcenter()
    {
        return $this->workcenter;
    }

    /**
     * @param Workcenter $workcenter
     * @return Agreement
     */
    public function setWorkcenter($workcenter)
    {
        $this->workcenter = $workcenter;
        return $this;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param ?\DateTimeInterface $startDate
     * @return Agreement
     */
    public function setStartDate(?\DateTimeInterface $startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return ?\DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param ?\DateTimeInterface $endDate
     * @return Agreement
     */
    public function setEndDate(?\DateTimeInterface $endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getSignDate()
    {
        return $this->signDate;
    }

    /**
     * @param ?\DateTimeInterface $signDate
     * @return Agreement
     */
    public function setSignDate(?\DateTimeInterface $signDate)
    {
        $this->signDate = $signDate;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultStartTime1()
    {
        return $this->defaultStartTime1;
    }

    /**
     * @param string $defaultStartTime1
     * @return Agreement
     */
    public function setDefaultStartTime1($defaultStartTime1)
    {
        $this->defaultStartTime1 = $defaultStartTime1;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultEndTime1()
    {
        return $this->defaultEndTime1;
    }

    /**
     * @param string $defaultEndTime1
     * @return Agreement
     */
    public function setDefaultEndTime1($defaultEndTime1)
    {
        $this->defaultEndTime1 = $defaultEndTime1;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultStartTime2()
    {
        return $this->defaultStartTime2;
    }

    /**
     * @param string $defaultStartTime2
     * @return Agreement
     */
    public function setDefaultStartTime2($defaultStartTime2)
    {
        $this->defaultStartTime2 = $defaultStartTime2;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultEndTime2()
    {
        return $this->defaultEndTime2;
    }

    /**
     * @param string $defaultEndTime2
     * @return Agreement
     */
    public function setDefaultEndTime2($defaultEndTime2)
    {
        $this->defaultEndTime2 = $defaultEndTime2;
        return $this;
    }

    /**
     * @return WorkDay[]|Collection
     */
    public function getWorkDays()
    {
        return $this->workDays;
    }

    /**
     * @param WorkDay[]|Collection $workDays
     * @return Agreement
     */
    public function setWorkDays($workDays)
    {
        $this->workDays = $workDays;
        return $this;
    }

    /**
     * @return Activity[]|Collection
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * @param Activity[]|Collection $activities
     * @return Agreement
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;
        return $this;
    }

    /**
     * @return AgreementEnrollment[]|Collection
     */
    public function getAgreementEnrollments()
    {
        return $this->agreementEnrollments;
    }

    /**
     * @param AgreementEnrollment[]|Collection $agreementEnrollments
     * @return Agreement
     */
    public function setAgreementEnrollments($agreementEnrollments)
    {
        $this->agreementEnrollments = $agreementEnrollments;
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
     * @return Agreement
     */
    public function setLocked(bool $locked)
    {
        $this->locked = $locked;
        return $this;
    }
}
