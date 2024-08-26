<?php

namespace App\Entity\ItpModule;

use App\Entity\Edu\Criterion;
use App\Entity\Edu\LearningOutcome;
use App\Repository\ItpModule\ActivityCriterionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityCriterionRepository::class)]
#[ORM\Table(name: 'itp_activity_learning_outcome')]
#[ORM\UniqueConstraint(name: 'activity_learning_outcome_unique', columns: ['activity_id', 'learning_outcome_id'])]
class ActivityLearningOutcome
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'assignedCriteria')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Activity $activity = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?LearningOutcome $learningOutcome = null;

    #[ORM\ManyToMany(targetEntity: Criterion::class)]
    #[ORM\JoinTable(name: 'itp_activity_learning_outcome_criterion')]
    private Collection $criteria;

    #[ORM\Column]
    private ?bool $shared = false;

    public function __construct()
    {
        $this->criteria = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(Activity $activity): static
    {
        $this->activity = $activity;

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

    public function getCriteria(): Collection
    {
        return $this->criteria;
    }

    public function setCriteria(Collection $criteria): ActivityLearningOutcome
    {
        $this->criteria = $criteria;
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
