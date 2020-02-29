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

use AppBundle\Entity\Edu\Grade;
use AppBundle\Entity\Edu\ReportTemplate;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Survey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WPT\ShiftRepository")
 * @ORM\Table(name="wpt_shift")
 */
class Shift
{
    const QUARTER_FIRST = 1;
    const QUARTER_SECOND = 2;
    const QUARTER_THIRD = 3;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $hours;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $quarter;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\Grade")
     * @ORM\JoinColumn(nullable=false)
     * @var Grade
     */
    private $grade;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Edu\StudentEnrollment")
     * @ORM\JoinTable(name="wpt_shift_student_enrollment")
     * @var StudentEnrollment[]|Collection
     */
    private $studentEnrollments;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $studentSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $companySurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $educationalTutorSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\ReportTemplate")
     * @var ReportTemplate
     */
    private $attendanceReportTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\ReportTemplate")
     * @var ReportTemplate
     */
    private $weeklyActivityReportTemplate;

    /**
     * @ORM\OneToMany(targetEntity="Agreement", mappedBy="shift")
     * @var Agreement[]|Collection
     */
    private $agreements;

    public function __construct()
    {
        $this->studentEnrollments = new ArrayCollection();
        $this->agreements = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
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
     * @return Shift
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @return Shift
     */
    public function setHours($hours)
    {
        $this->hours = $hours;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Shift
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuarter()
    {
        return $this->quarter;
    }

    /**
     * @param int $quarter
     * @return Shift
     */
    public function setQuarter($quarter)
    {
        $this->quarter = $quarter;
        return $this;
    }

    /**
     * @return Grade
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param Grade $grade
     * @return Shift
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
        return $this;
    }

    /**
     * @return StudentEnrollment[]|Collection
     */
    public function getStudentEnrollments()
    {
        return $this->studentEnrollments;
    }

    /**
     * @param StudentEnrollment[]|Collection $studentEnrollments
     * @return Shift
     */
    public function setStudentEnrollments($studentEnrollments)
    {
        $this->studentEnrollments = $studentEnrollments;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getStudentSurvey()
    {
        return $this->studentSurvey;
    }

    /**
     * @param Survey $studentSurvey
     * @return Shift
     */
    public function setStudentSurvey($studentSurvey)
    {
        $this->studentSurvey = $studentSurvey;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getCompanySurvey()
    {
        return $this->companySurvey;
    }

    /**
     * @param Survey $companySurvey
     * @return Shift
     */
    public function setCompanySurvey($companySurvey)
    {
        $this->companySurvey = $companySurvey;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getEducationalTutorSurvey()
    {
        return $this->educationalTutorSurvey;
    }

    /**
     * @param Survey $educationalTutorSurvey
     * @return Shift
     */
    public function setEducationalTutorSurvey($educationalTutorSurvey)
    {
        $this->educationalTutorSurvey = $educationalTutorSurvey;
        return $this;
    }

    /**
     * @return ReportTemplate
     */
    public function getAttendanceReportTemplate()
    {
        return $this->attendanceReportTemplate;
    }

    /**
     * @param ReportTemplate $attendanceReportTemplate
     * @return Shift
     */
    public function setAttendanceReportTemplate($attendanceReportTemplate)
    {
        $this->attendanceReportTemplate = $attendanceReportTemplate;
        return $this;
    }

    /**
     * @return ReportTemplate
     */
    public function getWeeklyActivityReportTemplate()
    {
        return $this->weeklyActivityReportTemplate;
    }

    /**
     * @param ReportTemplate $weeklyActivityReportTemplate
     * @return Shift
     */
    public function setWeeklyActivityReportTemplate($weeklyActivityReportTemplate)
    {
        $this->weeklyActivityReportTemplate = $weeklyActivityReportTemplate;
        return $this;
    }

    /**
     * @return Agreement[]|Collection
     */
    public function getAgreements()
    {
        return $this->agreements;
    }

    /**
     * @param Agreement[]|Collection $agreements
     * @return Shift
     */
    public function setAgreements($agreements)
    {
        $this->agreements = $agreements;
        return $this;
    }
}
