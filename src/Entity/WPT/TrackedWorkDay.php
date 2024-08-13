<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

namespace App\Entity\WPT;

use App\Repository\WPT\TrackedWorkDayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrackedWorkDayRepository::class)]
#[ORM\Table(name: 'wpt_tracked_work_day')]
#[ORM\UniqueConstraint(columns: ['agreement_enrollment_id', 'work_day_id'])]
class TrackedWorkDay
{
    public const NO_ABSENCE = 0;
    public const UNJUSTIFIED_ABSENCE = 1;
    public const JUSTIFIED_ABSENCE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: WorkDay::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?WorkDay $workDay = null;

    #[ORM\ManyToOne(targetEntity: AgreementEnrollment::class, inversedBy: 'trackedWorkDays')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AgreementEnrollment $agreementEnrollment = null;

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

    #[ORM\OneToMany(targetEntity: ActivityTracking::class, mappedBy: 'trackedWorkDay')]
    private Collection $trackedActivities;

    public function __construct()
    {
        $this->trackedActivities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWorkDay(): ?WorkDay
    {
        return $this->workDay;
    }

    public function setWorkDay(WorkDay $workDay): static
    {
        $this->workDay = $workDay;
        return $this;
    }

    public function getAgreementEnrollment(): ?AgreementEnrollment
    {
        return $this->agreementEnrollment;
    }

    public function setAgreementEnrollment(AgreementEnrollment $agreementEnrollment): static
    {
        $this->agreementEnrollment = $agreementEnrollment;
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
     * @return Collection<int, ActivityTracking>
     */
    public function getTrackedActivities(): Collection
    {
        return $this->trackedActivities;
    }
}
