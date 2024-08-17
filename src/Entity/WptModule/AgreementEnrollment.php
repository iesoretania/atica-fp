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

namespace App\Entity\WptModule;

use App\Entity\AnsweredSurvey;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Repository\WptModule\AgreementEnrollmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgreementEnrollmentRepository::class)]
#[ORM\Table(name: 'wpt_agreement_enrollment')]
#[ORM\UniqueConstraint(columns: ['agreement_id', 'student_enrollment_id'])]
class AgreementEnrollment implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Agreement::class, inversedBy: 'agreementEnrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agreement $agreement = null;

    #[ORM\ManyToOne(targetEntity: StudentEnrollment::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentEnrollment $studentEnrollment = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Person $workTutor = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Person $additionalWorkTutor = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Teacher $educationalTutor = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Teacher $additionalEducationalTutor = null;

    #[ORM\ManyToOne(targetEntity: AnsweredSurvey::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?AnsweredSurvey $studentSurvey = null;

    #[ORM\ManyToOne(targetEntity: AnsweredSurvey::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?AnsweredSurvey $companySurvey = null;

    #[ORM\OneToOne(targetEntity: Report::class, mappedBy: 'agreementEnrollment')]
    private ?Report $report = null;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\ManyToMany(targetEntity: Activity::class)]
    #[ORM\JoinTable(name: 'wpt_agreement_activity')]
    #[ORM\OrderBy(['code' => Criteria::ASC, 'description' => 'ASC'])]
    private Collection $activities;

    /**
     * @var Collection<int, TrackedWorkDay>
     */
    #[ORM\OneToMany(targetEntity: TrackedWorkDay::class, mappedBy: 'agreementEnrollment')]
    private Collection $trackedWorkDays;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->trackedWorkDays = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (!$this->getAgreement() instanceof Agreement || !$this->getStudentEnrollment() instanceof StudentEnrollment)
            ? ''
            : $this->getAgreement()->__toString() . ' - ' . $this->getStudentEnrollment()->__toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgreement(): ?Agreement
    {
        return $this->agreement;
    }

    public function setAgreement(Agreement $agreement): static
    {
        $this->agreement = $agreement;
        return $this;
    }

    public function getStudentEnrollment(): ?StudentEnrollment
    {
        return $this->studentEnrollment;
    }

    public function setStudentEnrollment(StudentEnrollment $studentEnrollment): static
    {
        $this->studentEnrollment = $studentEnrollment;
        return $this;
    }

    public function getWorkTutor(): ?Person
    {
        return $this->workTutor;
    }

    public function setWorkTutor(Person $workTutor): static
    {
        $this->workTutor = $workTutor;
        return $this;
    }

    public function getAdditionalWorkTutor(): ?Person
    {
        return $this->additionalWorkTutor;
    }

    public function setAdditionalWorkTutor(?Person $workTutor): static
    {
        $this->additionalWorkTutor = $workTutor;
        return $this;
    }

    public function getEducationalTutor(): ?Teacher
    {
        return $this->educationalTutor;
    }

    public function setEducationalTutor(Teacher $educationalTutor): static
    {
        $this->educationalTutor = $educationalTutor;
        return $this;
    }

    public function getAdditionalEducationalTutor(): ?Teacher
    {
        return $this->additionalEducationalTutor;
    }

    public function setAdditionalEducationalTutor(?Teacher $educationalTutor): static
    {
        $this->additionalEducationalTutor = $educationalTutor;
        return $this;
    }

    public function getStudentSurvey(): ?AnsweredSurvey
    {
        return $this->studentSurvey;
    }

    public function setStudentSurvey(?AnsweredSurvey $studentSurvey): static
    {
        $this->studentSurvey = $studentSurvey;
        return $this;
    }

    public function getCompanySurvey(): ?AnsweredSurvey
    {
        return $this->companySurvey;
    }

    public function setCompanySurvey(?AnsweredSurvey $companySurvey): static
    {
        $this->companySurvey = $companySurvey;
        return $this;
    }

    public function getReport(): ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report): static
    {
        $this->report = $report;
        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function setActivities(Collection $activities): static
    {
        $this->activities = $activities;
        return $this;
    }

    /**
     * @return Collection<int, TrackedWorkDay>
     */
    public function getTrackedWorkDays(): Collection
    {
        return $this->trackedWorkDays;
    }
}
