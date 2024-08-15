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

namespace App\Entity\WLT;

use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\Workcenter;
use App\Repository\WLT\AgreementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AgreementRepository::class)]
#[ORM\Table(name: 'wlt_agreement')]
#[ORM\UniqueConstraint(columns: ['project_id', 'student_enrollment_id', 'workcenter_id'])]
class Agreement implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'agreements')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\ManyToOne(targetEntity: Workcenter::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workcenter $workcenter = null;

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

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $defaultStartTime1 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $defaultEndTime1 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $defaultStartTime2 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $defaultEndTime2 = null;

    /**
     * @var Collection<int, AgreementActivityRealization>
     */
    #[ORM\OneToMany(targetEntity: AgreementActivityRealization::class, mappedBy: 'agreement')]
    private Collection $evaluatedActivityRealizations;

    /**
     * @var Collection<int, WorkDay>
     */
    #[ORM\OneToMany(targetEntity: WorkDay::class, mappedBy: 'agreement')]
    private Collection $workDays;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $workTutorRemarks = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $internalCode = null;

    public function __construct()
    {
        $this->evaluatedActivityRealizations = new ArrayCollection();
        $this->workDays = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (!$this->getStudentEnrollment() instanceof StudentEnrollment || !$this->getWorkcenter() instanceof Workcenter)
            ? ''
            : $this->getStudentEnrollment()->__toString() . ' - ' . $this->getWorkcenter()->__toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;
        return $this;
    }

    public function getWorkcenter(): ?Workcenter
    {
        return $this->workcenter;
    }

    public function setWorkcenter(Workcenter $workcenter): static
    {
        $this->workcenter = $workcenter;
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getDefaultStartTime1(): ?string
    {
        return $this->defaultStartTime1;
    }

    public function setDefaultStartTime1(?string $defaultStartTime1): static
    {
        $this->defaultStartTime1 = $defaultStartTime1;
        return $this;
    }

    public function getDefaultEndTime1(): ?string
    {
        return $this->defaultEndTime1;
    }

    public function setDefaultEndTime1(?string $defaultEndTime1): static
    {
        $this->defaultEndTime1 = $defaultEndTime1;
        return $this;
    }

    public function getDefaultStartTime2(): ?string
    {
        return $this->defaultStartTime2;
    }

    public function setDefaultStartTime2(?string $defaultStartTime2): static
    {
        $this->defaultStartTime2 = $defaultStartTime2;
        return $this;
    }

    public function getDefaultEndTime2(): ?string
    {
        return $this->defaultEndTime2;
    }

    public function setDefaultEndTime2(?string $defaultEndTime2): static
    {
        $this->defaultEndTime2 = $defaultEndTime2;
        return $this;
    }

    /**
     * @return Collection<int, AgreementActivityRealization>
     */
    public function getEvaluatedActivityRealizations(): Collection
    {
        return $this->evaluatedActivityRealizations;
    }

    /**
     * @return Collection<int, ActivityRealization>
     */
    public function getActivityRealizations(): Collection
    {
        $result = new ArrayCollection();

        foreach ($this->getEvaluatedActivityRealizations() as $evaluatedActivityRealization) {
            $result->add($evaluatedActivityRealization->getActivityRealization());
        }

        return $result;
    }

    /**
     * @return Collection<int, WorkDay>
     */
    public function getWorkDays(): Collection
    {
        return $this->workDays;
    }

    public function getWorkTutorRemarks(): ?string
    {
        return $this->workTutorRemarks;
    }

    public function setWorkTutorRemarks(?string $workTutorRemarks): static
    {
        $this->workTutorRemarks = $workTutorRemarks;
        return $this;
    }

    public function getInternalCode(): ?string
    {
        return $this->internalCode;
    }

    public function setInternalCode(?string $internalCode): static
    {
        $this->internalCode = $internalCode;
        return $this;
    }
}
