<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\Grade;
use App\Entity\Edu\Subject;
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

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\OneToMany(targetEntity: Activity::class, mappedBy: 'programGrade', orphanRemoval: true)]
    private Collection $activities;

    /**
     * @var Collection<int, ProgramGradeLearningOutcome>
     */
    #[ORM\OneToMany(targetEntity: ProgramGradeLearningOutcome::class, mappedBy: 'programGrade', orphanRemoval: true)]
    private Collection $programGradeLearningOutcomes;

    /**
     * @var Collection<int, Subject>
     */
    #[ORM\ManyToMany(targetEntity: Subject::class)]
    private Collection $subjects;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
        $this->programGradeLearningOutcomes = new ArrayCollection();
        $this->subjects = new ArrayCollection();
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
     * @return Collection<int, ProgramGradeLearningOutcome>
     */
    public function getProgramGradeLearningOutcomes(): Collection
    {
        return $this->programGradeLearningOutcomes;
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->subjects;
    }

    public function addSubject(Subject $subject): static
    {
        if (!$this->subjects->contains($subject)) {
            $this->subjects->add($subject);
        }

        return $this;
    }

    public function removeSubject(Subject $subject): static
    {
        $this->subjects->removeElement($subject);

        return $this;
    }
}
