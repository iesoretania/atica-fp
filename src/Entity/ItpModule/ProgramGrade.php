<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\Grade;
use App\Repository\ItpModule\ProgramGradeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramGradeRepository::class)]
#[ORM\Table(name: 'itp_program_grade')]
class ProgramGrade
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainingProgramGrades')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TrainingProgram $trainingProgram = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Grade $grade = null;

    #[ORM\Column(nullable: true)]
    private ?int $targetHours = null;

    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'programGrade', orphanRemoval: true)]
    private Collection $activities;

    #[ORM\OneToMany(targetEntity: CompanyProgram::class, mappedBy: 'programGrade', orphanRemoval: true)]
    private Collection $companyPrograms;

    #[ORM\OneToMany(targetEntity: ProgramGroup::class, mappedBy: 'programGrade', orphanRemoval: true)]
    private Collection $trainingProgramGroups;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->companyPrograms = new ArrayCollection();
        $this->trainingProgramGroups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrainingProgram(): ?TrainingProgram
    {
        return $this->trainingProgram;
    }

    public function setTrainingProgram(TrainingProgram $trainingProgram): static
    {
        $this->trainingProgram = $trainingProgram;

        return $this;
    }

    public function getGrade(): ?Grade
    {
        return $this->grade;
    }

    public function setGrade(Grade $grade): static
    {
        $this->grade = $grade;

        return $this;
    }

    public function getTargetHours(): ?int
    {
        return $this->targetHours;
    }

    public function setTargetHours(?int $targetHours): static
    {
        $this->targetHours = $targetHours;

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    /**
     * @return Collection<int, CompanyProgram>
     */
    public function getCompanyPrograms(): Collection
    {
        return $this->companyPrograms;
    }

    /**
     * @return Collection<int, ProgramGroup>
     */
    public function getTrainingProgramGroups(): Collection
    {
        return $this->trainingProgramGroups;
    }
}
