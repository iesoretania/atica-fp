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

namespace App\Entity\WLT;

use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\Workcenter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WLT\AgreementRepository")
 * @ORM\Table(name="wlt_agreement",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"project_id", "student_enrollment_id", "workcenter_id"})}))))
 * @UniqueEntity(fields={"project", "studentEnrollment", "workcenter"}, message="agreement.student_workcenter.unique")
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
     * @ORM\ManyToOne(targetEntity="App\Entity\WLT\Project", inversedBy="agreements")
     * @ORM\JoinColumn(nullable=false)
     * @var Project
     */
    private $project;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Workcenter")
     * @ORM\JoinColumn(nullable=false)
     * @var Workcenter
     */
    private $workcenter;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\StudentEnrollment")
     * @ORM\JoinColumn(nullable=false)
     * @var ?StudentEnrollment
     */
    private $studentEnrollment;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(nullable=false)
     * @var ?Person
     */
    private $workTutor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(nullable=true)
     * @var ?Person
     */
    private $additionalWorkTutor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\Teacher")
     * @ORM\JoinColumn(nullable=false)
     * @var ?Teacher
     */
    private $educationalTutor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\Teacher")
     * @ORM\JoinColumn(nullable=true)
     * @var ?Teacher
     */
    private $additionalEducationalTutor;

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

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $workTutorRemarks;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $internalCode;

    public function __construct()
    {
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
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return Agreement
     */
    public function setProject($project)
    {
        $this->project = $project;
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
     * @return ?Person
     */
    public function getAdditionalWorkTutor()
    {
        return $this->additionalWorkTutor;
    }

    /**
     * @param ?Person $workTutor
     * @return Agreement
     */
    public function setAdditionalWorkTutor(?Person $workTutor)
    {
        $this->additionalWorkTutor = $workTutor;
        return $this;
    }

    /**
     * @return Teacher
     */
    public function getEducationalTutor()
    {
        return $this->educationalTutor;
    }

    /**
     * @param Teacher $educationalTutor
     * @return Agreement
     */
    public function setEducationalTutor(?Teacher $educationalTutor)
    {
        $this->educationalTutor = $educationalTutor;
        return $this;
    }

    /**
     * @return ?Teacher
     */
    public function getAdditionalEducationalTutor()
    {
        return $this->additionalEducationalTutor;
    }

    /**
     * @param ?Teacher $educationalTutor
     * @return Agreement
     */
    public function setAdditionalEducationalTutor(?Teacher $educationalTutor)
    {
        $this->additionalEducationalTutor = $educationalTutor;
        return $this;
    }

    /**
     * @return ?\DateTime
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
     * @return ?\DateTimeInterface
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

    /**
     * @return string
     */
    public function getWorkTutorRemarks()
    {
        return $this->workTutorRemarks;
    }

    /**
     * @param string $workTutorRemarks
     * @return Agreement
     */
    public function setWorkTutorRemarks($workTutorRemarks)
    {
        $this->workTutorRemarks = $workTutorRemarks;
        return $this;
    }

    /**
     * @return string
     */
    public function getInternalCode()
    {
        return $this->internalCode;
    }

    /**
     * @param string $internalCode
     * @return Agreement
     */
    public function setInternalCode($internalCode)
    {
        $this->internalCode = $internalCode;
        return $this;
    }
}
