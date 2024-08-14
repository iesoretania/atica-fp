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

use App\Entity\Edu\Grade;
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\Subject;
use App\Entity\Survey;
use App\Repository\WPT\ShiftRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShiftRepository::class)]
#[ORM\Table(name: 'wpt_shift')]
class Shift implements \Stringable
{
    public const QUARTER_FIRST = 1;
    public const QUARTER_SECOND = 2;
    public const QUARTER_THIRD = 3;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $hours = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $type = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $quarter = null;

    #[ORM\ManyToOne(targetEntity: Subject::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Subject $subject = null;

    #[ORM\ManyToOne(targetEntity: Survey::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Survey $studentSurvey = null;

    #[ORM\ManyToOne(targetEntity: Survey::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Survey $companySurvey = null;

    #[ORM\ManyToOne(targetEntity: Survey::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Survey $educationalTutorSurvey = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $attendanceReportTemplate = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $finalReportTemplate = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $weeklyActivityReportTemplate = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $activitySummaryReportTemplate = null;

    /**
     * @var Collection<int, Agreement>
     */
    #[ORM\OneToMany(targetEntity: Agreement::class, mappedBy: 'shift')]
    private Collection $agreements;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'shift')]
    #[ORM\OrderBy(['code' => Criteria::ASC, 'description' => 'ASC'])]
    private Collection $activities;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $locked = null;

    public function __construct()
    {
        $this->agreements = new ArrayCollection();
        $this->activities = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getHours(): ?int
    {
        return $this->hours;
    }

    public function setHours(int $hours): static
    {
        $this->hours = $hours;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getQuarter(): ?int
    {
        return $this->quarter;
    }

    public function setQuarter(int $quarter): static
    {
        $this->quarter = $quarter;
        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(Subject $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getGrade(): ?Grade
    {
        return $this->subject instanceof Subject ? $this->subject->getGrade() : null;
    }

    public function getStudentSurvey(): ?Survey
    {
        return $this->studentSurvey;
    }

    public function setStudentSurvey(?Survey $studentSurvey): static
    {
        $this->studentSurvey = $studentSurvey;
        return $this;
    }

    public function getCompanySurvey(): ?Survey
    {
        return $this->companySurvey;
    }

    public function setCompanySurvey(?Survey $companySurvey): static
    {
        $this->companySurvey = $companySurvey;
        return $this;
    }

    public function getEducationalTutorSurvey(): ?Survey
    {
        return $this->educationalTutorSurvey;
    }

    public function setEducationalTutorSurvey(?Survey $educationalTutorSurvey): static
    {
        $this->educationalTutorSurvey = $educationalTutorSurvey;
        return $this;
    }

    public function getAttendanceReportTemplate(): ?ReportTemplate
    {
        return $this->attendanceReportTemplate;
    }

    public function setAttendanceReportTemplate(?ReportTemplate $attendanceReportTemplate): static
    {
        $this->attendanceReportTemplate = $attendanceReportTemplate;
        return $this;
    }

    public function getWeeklyActivityReportTemplate(): ?ReportTemplate
    {
        return $this->weeklyActivityReportTemplate;
    }

    public function setWeeklyActivityReportTemplate(?ReportTemplate $weeklyActivityReportTemplate): static
    {
        $this->weeklyActivityReportTemplate = $weeklyActivityReportTemplate;
        return $this;
    }

    public function getFinalReportTemplate(): ?ReportTemplate
    {
        return $this->finalReportTemplate;
    }

    public function setFinalReportTemplate(?ReportTemplate $finalReportTemplate): static
    {
        $this->finalReportTemplate = $finalReportTemplate;
        return $this;
    }

    public function getActivitySummaryReportTemplate(): ?ReportTemplate
    {
        return $this->activitySummaryReportTemplate;
    }

    public function setActivitySummaryReportTemplate(?ReportTemplate $activitySummaryReportTemplate): static
    {
        $this->activitySummaryReportTemplate = $activitySummaryReportTemplate;
        return $this;
    }

    /**
     * @return Collection<int, Agreement>
     */
    public function getAgreements(): Collection
    {
        return $this->agreements;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;
        return $this;
    }
}
