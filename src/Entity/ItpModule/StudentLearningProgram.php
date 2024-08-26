<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\StudentEnrollment;
use App\Entity\Workcenter;
use App\Repository\ItpModule\StudentLearningProgramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentLearningProgramRepository::class)]
#[ORM\Table(name: 'itp_student_learning_program')]
class StudentLearningProgram
{
    public const MODE_DEFAULT = 0;
    public const MODE_GENERAL = 1;
    public const MODE_INTENSIVE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'studentLearningPrograms')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TrainingProgramGroup $trainingProgramGroup = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentEnrollment $studentEnrollment = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workcenter $workcenter = null;

    #[ORM\Column]
    private ?int $modality = self::MODE_DEFAULT;

    #[ORM\Column]
    private ?bool $authorizationNeeded = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $authorizationDescription = null;

    #[ORM\Column]
    private ?bool $adaptationNeeded = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adaptationDescription = null;

    /**
     * @var Collection<int, WorkDay>
     */
    #[ORM\OneToMany(targetEntity: WorkDay::class, mappedBy: 'studentLearningProgram', orphanRemoval: true)]
    private Collection $workDays;

    public function __construct()
    {
        $this->workDays = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTrainingProgramGroup(): ?TrainingProgramGroup
    {
        return $this->trainingProgramGroup;
    }

    public function setTrainingProgramGroup(TrainingProgramGroup $trainingProgramGroup): static
    {
        $this->trainingProgramGroup = $trainingProgramGroup;

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

    public function getWorkcenter(): ?Workcenter
    {
        return $this->workcenter;
    }

    public function setWorkcenter(?Workcenter $workcenter): static
    {
        $this->workcenter = $workcenter;

        return $this;
    }

    public function getModality(): ?int
    {
        return $this->modality;
    }

    public function setModality(int $modality): static
    {
        $this->modality = $modality;

        return $this;
    }

    public function isAuthorizationNeeded(): ?bool
    {
        return $this->authorizationNeeded;
    }

    public function setAuthorizationNeeded(bool $authorizationNeeded): static
    {
        $this->authorizationNeeded = $authorizationNeeded;

        return $this;
    }

    public function getAuthorizationDescription(): ?string
    {
        return $this->authorizationDescription;
    }

    public function setAuthorizationDescription(?string $authorizationDescription): static
    {
        $this->authorizationDescription = $authorizationDescription;

        return $this;
    }

    public function isAdaptationNeeded(): ?bool
    {
        return $this->adaptationNeeded;
    }

    public function setAdaptationNeeded(bool $adaptationNeeded): static
    {
        $this->adaptationNeeded = $adaptationNeeded;

        return $this;
    }

    public function getAdaptationDescription(): ?string
    {
        return $this->adaptationDescription;
    }

    public function setAdaptationDescription(?string $adaptationDescription): static
    {
        $this->adaptationDescription = $adaptationDescription;

        return $this;
    }

    /**
     * @return Collection<int, WorkDay>
     */
    public function getWorkDays(): Collection
    {
        return $this->workDays;
    }
}
