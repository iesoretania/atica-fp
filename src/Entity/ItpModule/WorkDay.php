<?php

namespace App\Entity\ItpModule;

use App\Repository\ItpModule\WorkDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;

#[ORM\Entity(repositoryClass: WorkDayRepository::class)]
#[ORM\Table(name: 'itp_work_day')]
class WorkDay
{
    public const ABSENCE_NONE = 0;
    public const ABSENCE_UNJUSTIFIED = 1;
    public const ABSENCE_JUSTIFIED = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'workDays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?StudentProgramWorkcenter $studentProgramWorkcenter = null;

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
    private ?bool $locked = false;

    #[ORM\Column]
    private ?int $absence = self::ABSENCE_NONE;

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

    public function getStudentProgramWorkcenter(): ?StudentProgramWorkcenter
    {
        return $this->studentProgramWorkcenter;
    }

    public function setStudentProgramWorkcenter(StudentProgramWorkcenter $studentProgramWorkcenter): static
    {
        $this->studentProgramWorkcenter = $studentProgramWorkcenter;

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

    #[Assert\Callback]
    public function validate(ExecutionContext $context, $payload): void
    {
        if (!empty($this->getStartTime1()) && empty($this->getEndTime1())) {
            $context->buildViolation('calendar.end_time_needed')
                ->atPath('endTime1')
                ->addViolation();
        }
        if (empty($this->getStartTime1()) && !empty($this->getEndTime1())) {
            $context->buildViolation('calendar.start_time_needed')
                ->atPath('startTime1')
                ->addViolation();
        }
        if (!empty($this->getStartTime2()) && empty($this->getEndTime2())) {
            $context->buildViolation('calendar.end_time_needed')
                ->atPath('endTime2')
                ->addViolation();
        }
        if (empty($this->getStartTime2()) && !empty($this->getEndTime2())) {
            $context->buildViolation('calendar.start_time_needed')
                ->atPath('startTime2')
                ->addViolation();
        }
        if (empty($this->getStartTime1()) && !empty($this->getStartTime2())) {
            $context->buildViolation('calendar.first_start_time_needed')
                ->atPath('startTime2')
                ->addViolation();
        }
    }

    public function getTimeHours(): int
    {
        if (empty($this->getStartTime1())) {
            return $this->getHours();
        }
        $startTime1 = $this->convertTimeToHours($this->getStartTime1());
        $endTime1 = $this->convertTimeToHours($this->getEndTime1());
        $time1 = $endTime1 - $startTime1;
        if ($time1 < 0) {
            $time1 += 2400;
        }
        if (empty($this->getStartTime2())) {
            return $time1;
        }
        $startTime2 = $this->convertTimeToHours($this->getStartTime2());
        $endTime2 = $this->convertTimeToHours($this->getEndTime2());
        $time2 = $endTime2 - $startTime2;
        if ($time2 < 0) {
            $time2 += 2400;
        }
        return ($time1 + $time2);
    }

    private function convertTimeToHours(string $time): int
    {
        $timeArray = explode(':', $time);
        return (int) ($timeArray[0] * 100.0) + (int) ($timeArray[1] * 100.0 / 60.0);
    }
}
