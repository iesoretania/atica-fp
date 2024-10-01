<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\LearningOutcome;
use App\Repository\ItpModule\ProgramGradeLearningOutcomeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramGradeLearningOutcomeRepository::class)]
#[ORM\Table(name: 'itp_program_grade_learning_outcome')]
#[ORM\UniqueConstraint(name: 'itp_program_grade_learning_outcome_unique', columns: ['program_grade_id', 'learning_outcome_id'])]
class ProgramGradeLearningOutcome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'programGradeLearningOutcomes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProgramGrade $programGrade = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LearningOutcome $learningOutcome = null;

    #[ORM\Column]
    private ?bool $shared = false;

    public function __construct()
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgramGrade(): ?ProgramGrade
    {
        return $this->programGrade;
    }

    public function setProgramGrade(?ProgramGrade $programGrade): static
    {
        $this->programGrade = $programGrade;

        return $this;
    }

    public function getLearningOutcome(): ?LearningOutcome
    {
        return $this->learningOutcome;
    }

    public function setLearningOutcome(LearningOutcome $learningOutcome): static
    {
        $this->learningOutcome = $learningOutcome;

        return $this;
    }

    public function isShared(): ?bool
    {
        return $this->shared;
    }

    public function setShared(bool $shared): static
    {
        $this->shared = $shared;

        return $this;
    }
}
