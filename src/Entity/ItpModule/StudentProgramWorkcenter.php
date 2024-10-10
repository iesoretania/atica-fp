<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\Teacher;
use App\Entity\Person;
use App\Entity\Workcenter;
use App\Repository\ItpModule\StudentProgramWorkcenterRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentProgramWorkcenterRepository::class)]
#[ORM\Table(name: 'itp_student_program_workcenter')]
class StudentProgramWorkcenter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'studentProgramWorkcenters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProgram $studentProgram = null;

    /**
     * @var Collection<int, WorkDay>
     */
    #[ORM\OneToMany(targetEntity: WorkDay::class, mappedBy: 'studentProgramWorkcenter', orphanRemoval: true)]
    private Collection $workDays;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workcenter $workcenter = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Teacher $educationalTutor = null;

    #[ORM\ManyToOne]
    private ?Teacher $additionalEducationalTutor = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Person $workTutor = null;

    #[ORM\ManyToOne]
    private ?Person $additionalWorkTutor = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    public function __construct()
    {
        $this->workDays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudentProgram(): ?StudentProgram
    {
        return $this->studentProgram;
    }

    public function setStudentProgram(?StudentProgram $studentProgram): static
    {
        $this->studentProgram = $studentProgram;

        return $this;
    }

    /**
     * @return Collection<int, WorkDay>
     */
    public function getWorkDays(): Collection
    {
        return $this->workDays;
    }

    public function getWorkcenter(): ?Workcenter
    {
        return $this->workcenter;
    }

    public function setWorkcenter(?Workcenter $workcenter): static
    {
        $this->workcenter = $workcenter;

        return $this;
    }

    public function getEducationalTutor(): ?Teacher
    {
        return $this->educationalTutor;
    }

    public function setEducationalTutor(?Teacher $educationalTutor): static
    {
        $this->educationalTutor = $educationalTutor;

        return $this;
    }

    public function getAdditionalEducationalTutor(): ?Teacher
    {
        return $this->additionalEducationalTutor;
    }

    public function setAdditionalEducationalTutor(?Teacher $additionalEducationalTutor): static
    {
        $this->additionalEducationalTutor = $additionalEducationalTutor;

        return $this;
    }

    public function getWorkTutor(): ?Person
    {
        return $this->workTutor;
    }

    public function setWorkTutor(?Person $workTutor): static
    {
        $this->workTutor = $workTutor;

        return $this;
    }

    public function getAdditionalWorkTutor(): ?Person
    {
        return $this->additionalWorkTutor;
    }

    public function setAdditionalWorkTutor(?Person $additionalWorkTutor): static
    {
        $this->additionalWorkTutor = $additionalWorkTutor;

        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;

        return $this;
    }
}
