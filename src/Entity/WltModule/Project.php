<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Entity\WltModule;

use App\Entity\Edu\Group;
use App\Entity\Edu\ReportTemplate;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Organization;
use App\Entity\Person;
use App\Entity\Survey;
use App\Repository\WltModule\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'wlt_project')]
class Project implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Person $manager = null;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\ManyToMany(targetEntity: Group::class)]
    #[ORM\JoinTable(name: 'wlt_project_group')]
    private Collection $groups;

    /**
     * @var Collection<int, StudentEnrollment>
     */
    #[ORM\ManyToMany(targetEntity: StudentEnrollment::class)]
    #[ORM\JoinTable(name: 'wlt_project_student_enrollment')]
    private Collection $studentEnrollments;

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
    private ?ReportTemplate $weeklyActivityReportTemplate = null;

    /**
     * @var Collection<int, Agreement>
     */
    #[ORM\OneToMany(targetEntity: Agreement::class, mappedBy: 'project')]
    private Collection $agreements;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'project')]
    private Collection $activities;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $locked = false;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->studentEnrollments = new ArrayCollection();
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

    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getManager(): ?Person
    {
        return $this->manager;
    }

    public function setManager(Person $manager): static
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function setGroups(Collection $groups): static
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * @return Collection<int, StudentEnrollment>
     */
    public function getStudentEnrollments(): Collection
    {
        return $this->studentEnrollments;
    }

    public function setStudentEnrollments(Collection $studentEnrollments): static
    {
        $this->studentEnrollments = $studentEnrollments;
        return $this;
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
