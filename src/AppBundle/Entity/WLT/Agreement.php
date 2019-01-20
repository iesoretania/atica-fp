<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Person;
use AppBundle\Entity\Workcenter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WLT\AgreementRepository")
 * @ORM\Table(name="wlt_agreement",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"student_enrollment_id", "workcenter_id"})}))))
 * @UniqueEntity(fields={"studentEnrollment", "workcenter"}, message="agreement.student_workcenter.unique")
 */
class Agreement
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Workcenter")
     * @ORM\JoinColumn(nullable=false)
     * @var Workcenter
     */
    private $workcenter;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\StudentEnrollment")
     * @ORM\JoinColumn(nullable=false)
     * @var StudentEnrollment
     */
    private $studentEnrollment;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(nullable=false)
     * @var Person
     */
    private $workTutor;

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
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $studentPollSubmitted;

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
     * @ORM\OneToMany(targetEntity="AgreementActivityRealization", mappedBy="agreement")
     * @var AgreementActivityRealization[]
     */
    private $evaluatedActivityRealizations;

    /**
     * @ORM\OneToMany(targetEntity="WorkDay", mappedBy="agreement")
     * @var WorkDay[]
     */
    private $workDays;

    public function __construct()
    {
        $this->studentPollSubmitted = false;
        $this->evaluatedActivityRealizations = new ArrayCollection();
        $this->workDays = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getStudentEnrollment() . ' - ' . $this->getWorkcenter();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
    public function setWorkcenter(Workcenter $workcenter)
    {
        $this->workcenter = $workcenter;
        return $this;
    }

    /**
     * @return StudentEnrollment
     */
    public function getStudentEnrollment()
    {
        return $this->studentEnrollment;
    }

    /**
     * @param StudentEnrollment $studentEnrollment
     * @return Agreement
     */
    public function setStudentEnrollment(StudentEnrollment $studentEnrollment)
    {
        $this->studentEnrollment = $studentEnrollment;
        return $this;
    }

    /**
     * @return Person
     */
    public function getWorkTutor()
    {
        return $this->workTutor;
    }

    /**
     * @param Person $workTutor
     * @return Agreement
     */
    public function setWorkTutor(Person $workTutor)
    {
        $this->workTutor = $workTutor;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return Agreement
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     * @return Agreement
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return bool
     */
    public function isStudentPollSubmitted()
    {
        return $this->studentPollSubmitted;
    }

    /**
     * @param bool $studentPollSubmitted
     * @return Agreement
     */
    public function setStudentPollSubmitted($studentPollSubmitted)
    {
        $this->studentPollSubmitted = $studentPollSubmitted;
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
     * @return AgreementActivityRealization[]
     */
    public function getEvaluatedActivityRealizations()
    {
        return $this->evaluatedActivityRealizations;
    }

    /**
     * @param AgreementActivityRealization[] $evaluatedActivityRealizations
     * @return Agreement
     */
    public function setEvaluatedActivityRealizations($evaluatedActivityRealizations)
    {
        $this->evaluatedActivityRealizations = $evaluatedActivityRealizations;
        return $this;
    }

    /**
     * @return ActivityRealization[]|Collection
     */
    public function getActivityRealizations()
    {
        $result = new ArrayCollection();

        if ($this->getEvaluatedActivityRealizations()) {
            foreach ($this->getEvaluatedActivityRealizations() as $evaluatedActivityRealization) {
                $result->add($evaluatedActivityRealization->getActivityRealization());
            }
        }
        return $result;
    }

    /**
     * @return WorkDay[]
     */
    public function getWorkDays()
    {
        return $this->workDays;
    }
}
