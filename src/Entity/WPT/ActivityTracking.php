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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WPT\ActivityTrackingRepository")
 * @ORM\Table(name="wpt_activity_tracking")
 */
class ActivityTracking
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="TrackedWorkDay", inversedBy="trackedActivities")
     * @ORM\JoinColumn(nullable=false)
     * @var WorkDay
     */
    private $trackedWorkDay;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Activity")
     * @var Activity
     */
    protected $activity;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $notes;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $hours;

    /**
     * @return WorkDay
     */
    public function getTrackedWorkDay()
    {
        return $this->trackedWorkDay;
    }

    /**
     * @param TrackedWorkDay $trackedWorkDay
     * @return ActivityTracking
     */
    public function setTrackedWorkDay($trackedWorkDay)
    {
        $this->trackedWorkDay = $trackedWorkDay;
        return $this;
    }

    /**
     * @return Activity
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * @param Activity $activity
     * @return ActivityTracking
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     * @return ActivityTracking
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return int
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * @param int $hours
     * @return ActivityTracking
     */
    public function setHours($hours)
    {
        $this->hours = $hours;
        return $this;
    }
}
