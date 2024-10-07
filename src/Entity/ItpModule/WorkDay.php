<?php

namespace App\Entity\ItpModule;

use App\Repository\ItpModule\WorkDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkDayRepository::class)]
#[ORM\Table(name: 'itp_work_day')]
class WorkDay
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'workDays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProgram $studentLearningProgram = null;

    /**
     * @var Collection<int, Activity>
     */
    #[ORM\ManyToMany(targetEntity: Activity::class)]
    #[ORM\JoinTable(name: 'itp_work_day_activity')]
    private Collection $activities;

    #[ORM\Column]
    private ?int $hours = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $otherActivities = null;

    #[ORM\Column]
    private ?bool $locked = null;

    #[ORM\Column]
    private ?int $absence = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $startTime1 = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $endTime1 = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $startTime2 = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $endTime2 = null;

    public function __construct()
    {
        $this->activities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStudentLearningProgram(): ?StudentProgram
    {
        return $this->studentLearningProgram;
    }

    public function setStudentLearningProgram(StudentProgram $studentLearningProgram): static
    {
        $this->studentLearningProgram = $studentLearningProgram;

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getActivities(): Collection
    {
        return $this->activities;
    }

    public function addActivity(Activity $activity): static
    {
        if (!$this->activities->contains($activity)) {
            $this->activities->add($activity);
        }

        return $this;
    }

    public function removeActivity(Activity $activity): static
    {
        $this->activities->removeElement($activity);

        return $this;
    }

    public function getHours(): ?int
    {
        return $this->hours;
    }

    public function setHours(int $hours): static
    {
        $this->hours = $hours;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getOtherActivities(): ?string
    {
        return $this->otherActivities;
    }

    public function setOtherActivities(?string $otherActivities): static
    {
        $this->otherActivities = $otherActivities;

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

    public function getAbsence(): ?int
    {
        return $this->absence;
    }

    public function setAbsence(int $absence): static
    {
        $this->absence = $absence;

        return $this;
    }

    public function getStartTime1(): ?string
    {
        return $this->startTime1;
    }

    public function setStartTime1(?string $startTime1): static
    {
        $this->startTime1 = $startTime1;

        return $this;
    }

    public function getEndTime1(): ?string
    {
        return $this->endTime1;
    }

    public function setEndTime1(?string $endTime1): static
    {
        $this->endTime1 = $endTime1;

        return $this;
    }

    public function getStartTime2(): ?string
    {
        return $this->startTime2;
    }

    public function setStartTime2(?string $startTime2): static
    {
        $this->startTime2 = $startTime2;

        return $this;
    }

    public function getEndTime2(): ?string
    {
        return $this->endTime2;
    }

    public function setEndTime2(?string $endTime2): static
    {
        $this->endTime2 = $endTime2;

        return $this;
    }
}
