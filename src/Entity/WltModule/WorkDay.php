<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Entity\WltModule;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContext;

#[ORM\Entity]
#[ORM\Table(name: 'wlt_work_day')]
#[ORM\UniqueConstraint(columns: ['agreement_id', 'date'])]
class WorkDay
{
    public const NO_ABSENCE = 0;
    public const UNJUSTIFIED_ABSENCE = 1;
    public const JUSTIFIED_ABSENCE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Agreement::class, inversedBy: 'workDays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agreement $agreement = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $hours = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $otherActivities = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $locked = false;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $absence = self::NO_ABSENCE;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $startTime1 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $endTime1 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $startTime2 = null;

    #[ORM\Column(type: Types::STRING, length: 5, nullable: true)]
    #[Assert\Regex('/^\d\d:\d\d$/')]
    private ?string $endTime2 = null;

    /**
     * @var Collection<int, ActivityRealization>
     */
    #[ORM\ManyToMany(targetEntity: ActivityRealization::class)]
    #[ORM\JoinTable('wlt_tracking')]
    private Collection $activityRealizations;

    public function __construct()
    {
        $this->activityRealizations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgreement(): ?Agreement
    {
        return $this->agreement;
    }

    public function setAgreement(Agreement $agreement): static
    {
        $this->agreement = $agreement;
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

    /**
     * @return Collection<int, ActivityRealization>
     */
    public function getActivityRealizations(): Collection
    {
        return $this->activityRealizations;
    }

    public function setActivityRealizations(Collection $activityRealizations): static
    {
        $this->activityRealizations = $activityRealizations;
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
        if (empty($this->getStartTime1()) || empty($this->getEndTime1())) {
            return $this->getHours();
        }
        $startTime1 = $this->convertTimeToHours($this->getStartTime1());
        $endTime1 = $this->convertTimeToHours($this->getEndTime1());
        $time1 = $endTime1 - $startTime1;
        if ($time1 < 0) {
            $time1 += 2400;
        }
        if (empty($this->getStartTime2()) || empty($this->getEndTime2())) {
            return $time1 / 100;
        }
        $startTime2 = $this->convertTimeToHours($this->getStartTime2());
        $endTime2 = $this->convertTimeToHours($this->getEndTime2());
        $time2 = $endTime2 - $startTime2;
        if ($time2 < 0) {
            $time2 += 2400;
        }
        return ($time1 + $time2) / 100;
    }

    private function convertTimeToHours(?string $time): int
    {
        if ($time === null) return 0;
        $timeArray = explode(':', $time);
        return (int) ($timeArray[0] * 100.0) + (int) ($timeArray[1] * 100.0 / 60.0);
    }
}
