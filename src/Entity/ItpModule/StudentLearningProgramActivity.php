<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\PerformanceScaleValue;
use App\Entity\Person;
use App\Repository\ItpModule\StudentLearningProgramActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StudentLearningProgramActivityRepository::class)]
class StudentLearningProgramActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Activity $activity = null;

    #[ORM\Column]
    private ?bool $locked = null;

    #[ORM\ManyToOne]
    private ?PerformanceScaleValue $scaleValue = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    /**
     * @var Collection<int, StudentLearningProgramActivityComment>
     */
    #[ORM\OneToMany(targetEntity: StudentLearningProgramActivityComment::class, mappedBy: 'studentLearningProgramActivity', orphanRemoval: true)]
    private Collection $comments;

    #[ORM\ManyToOne]
    private ?Person $valuedBy = null;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(?Activity $activity): static
    {
        $this->activity = $activity;

        return $this;
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

    public function getScaleValue(): ?PerformanceScaleValue
    {
        return $this->scaleValue;
    }

    public function setScaleValue(?PerformanceScaleValue $scaleValue): static
    {
        $this->scaleValue = $scaleValue;

        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return Collection<int, StudentLearningProgramActivityComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function getValuedBy(): ?Person
    {
        return $this->valuedBy;
    }

    public function setValuedBy(?Person $valuedBy): static
    {
        $this->valuedBy = $valuedBy;

        return $this;
    }
}
