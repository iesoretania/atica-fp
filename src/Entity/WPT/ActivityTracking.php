<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

use App\Repository\WPT\ActivityTrackingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityTrackingRepository::class)]
#[ORM\Table(name: 'wpt_activity_tracking')]
class ActivityTracking
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: TrackedWorkDay::class, inversedBy: 'trackedActivities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TrackedWorkDay $trackedWorkDay = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Activity::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Activity $activity = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $hours = 0;


    public function getTrackedWorkDay(): ?TrackedWorkDay
    {
        return $this->trackedWorkDay;
    }

    public function setTrackedWorkDay(TrackedWorkDay $trackedWorkDay): static
    {
        $this->trackedWorkDay = $trackedWorkDay;
        return $this;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
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
}
