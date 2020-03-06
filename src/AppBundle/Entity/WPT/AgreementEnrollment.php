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

namespace AppBundle\Entity\WPT;

use AppBundle\Entity\AnsweredSurvey;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use AppBundle\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WPT\AgreementEnrollmentRepository")
 * @ORM\Table(name="wpt_agreement_enrollment",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"agreement_id", "student_enrollment_id"})}))))
 * @UniqueEntity(fields={"agreement", "studentEnrollment"}, message="agreement.student_agreement.unique")
 */
class AgreementEnrollment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Agreement", inversedBy="agreementEnrollments")
     * @ORM\JoinColumn(nullable=false)
     * @var Agreement
     */
    private $agreement;

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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\Teacher")
     * @ORM\JoinColumn(nullable=false)
     * @var Teacher
     */
    private $educationalTutor;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AnsweredSurvey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var AnsweredSurvey
     */
    private $studentSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AnsweredSurvey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var AnsweredSurvey
     */
    private $companySurvey;

    /**
     * @ORM\OneToOne(targetEntity="Report", mappedBy="agreementEnrollment")
     * @var Report
     */
    private $report;

    /**
     * @ORM\ManyToMany(targetEntity="Activity")
     * @ORM\JoinTable(name="wpt_agreement_activity")
     * @ORM\OrderBy({"code"="ASC", "description"="ASC"})
     * @var Activity[]|Collection
     */
    private $activities;

    /**
     * @ORM\OneToMany(targetEntity="TrackedWorkDay", mappedBy="agreementEnrollment")
     * @var TrackedWorkDay[]|Collection
     */
    private $trackedWorkDays;

    /**
     * AgreementEnrollment constructor.
     */
    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->trackedWorkDays = new ArrayCollection();
    }

    public function __toString()
    {
        return (string) $this->getAgreement() . ' - ' . $this->getStudentEnrollment();
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
     * @return AgreementEnrollment
     */
    public function setAgreement($agreement)
    {
        $this->agreement = $agreement;
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
     * @return AgreementEnrollment
     */
    public function setStudentEnrollment($studentEnrollment)
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
     * @return AgreementEnrollment
     */
    public function setWorkTutor($workTutor)
    {
        $this->workTutor = $workTutor;
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
     * @return AgreementEnrollment
     */
    public function setEducationalTutor($educationalTutor)
    {
        $this->educationalTutor = $educationalTutor;
        return $this;
    }

    /**
     * @return AnsweredSurvey
     */
    public function getStudentSurvey()
    {
        return $this->studentSurvey;
    }

    /**
     * @param AnsweredSurvey $studentSurvey
     * @return AgreementEnrollment
     */
    public function setStudentSurvey($studentSurvey)
    {
        $this->studentSurvey = $studentSurvey;
        return $this;
    }

    /**
     * @return AnsweredSurvey
     */
    public function getCompanySurvey()
    {
        return $this->companySurvey;
    }

    /**
     * @param AnsweredSurvey $companySurvey
     * @return AgreementEnrollment
     */
    public function setCompanySurvey($companySurvey)
    {
        $this->companySurvey = $companySurvey;
        return $this;
    }

    /**
     * @return Report
     */
    public function getReport()
    {
        return $this->report;
    }

    /**
     * @param Report $report
     * @return AgreementEnrollment
     */
    public function setReport($report)
    {
        $this->report = $report;
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
     * @return AgreementEnrollment
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;
        return $this;
    }

    /**
     * @return TrackedWorkDay[]|Collection
     */
    public function getTrackedWorkDays()
    {
        return $this->trackedWorkDays;
    }

    /**
     * @param TrackedWorkDay[]|Collection $trackedWorkDays
     * @return AgreementEnrollment
     */
    public function setTrackedWorkDays($trackedWorkDays)
    {
        $this->trackedWorkDays = $trackedWorkDays;
        return $this;
    }
}
