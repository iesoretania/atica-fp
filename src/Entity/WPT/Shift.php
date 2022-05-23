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

namespace App\Entity\WPT;

use App\Entity\Edu\Grade;
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Subject;
use App\Entity\Survey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WPT\ShiftRepository")
 * @ORM\Table(name="wpt_shift")
 */
class Shift
{
    public const QUARTER_FIRST = 1;
    public const QUARTER_SECOND = 2;
    public const QUARTER_THIRD = 3;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\Subject")
     * @ORM\JoinColumn(nullable=false)
     * @var Subject
     */
    private $subject;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var ?Survey
     */
    private $studentSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var ?Survey
     */
    private $companySurvey;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var ?Survey
     */
    private $educationalTutorSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\ReportTemplate")
     * @var ReportTemplate
     */
    private $attendanceReportTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\ReportTemplate")
     * @var ReportTemplate
     */
    private $finalReportTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\ReportTemplate")
     * @var ReportTemplate
     */
    private $weeklyActivityReportTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\ReportTemplate")
     * @var ReportTemplate
     */
    private $activitySummaryReportTemplate;

    /**
     * @ORM\OneToMany(targetEntity="Agreement", mappedBy="shift")
     * @var Agreement[]|Collection
     */
    private $agreements;

    /**
     * @ORM\OneToMany(targetEntity="Activity", mappedBy="shift")
     * @ORM\OrderBy({"code": "ASC", "description": "ASC"})
     * @var Activity[]|Collection
     */
    private $activities;

    public function __construct()
    {
        $this->agreements = new ArrayCollection();
        $this->activities = new ArrayCollection();
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
     * @return Subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param Subject $subject
     * @return Shift
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return ?Grade
     */
    public function getGrade()
    {
        return $this->subject ? $this->subject->getGrade() : null;
    }

    /**
     * @return ?Survey
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
     * @return ?Survey
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
     * @return ?Survey
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
     * @return ReportTemplate
     */
    public function getFinalReportTemplate()
    {
        return $this->finalReportTemplate;
    }

    /**
     * @param ReportTemplate $finalReportTemplate
     * @return Shift
     */
    public function setFinalReportTemplate($finalReportTemplate)
    {
        $this->finalReportTemplate = $finalReportTemplate;
        return $this;
    }

    /**
     * @return ReportTemplate
     */
    public function getActivitySummaryReportTemplate()
    {
        return $this->activitySummaryReportTemplate;
    }

    /**
     * @param ReportTemplate $activitySummaryReportTemplate
     * @return Shift
     */
    public function setActivitySummaryReportTemplate($activitySummaryReportTemplate)
    {
        $this->activitySummaryReportTemplate = $activitySummaryReportTemplate;
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

    /**
     * @return Activity[]|Collection
     */
    public function getActivities()
    {
        return $this->activities;
    }

    /**
     * @param Activity[]|Collection $activities
     * @return Shift
     */
    public function setActivities($activities)
    {
        $this->activities = $activities;
        return $this;
    }
}
