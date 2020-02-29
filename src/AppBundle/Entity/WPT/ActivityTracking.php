<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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

namespace AppBundle\Entity\WPT;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WPT\ActivityTrackingRepository")
 * @ORM\Table(name="wpt_activity_tracking")
 */
class ActivityTracking
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Workday", inversedBy="trackedActivities")
     * @var Workday
     */
    protected $workday;

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
     * @return Workday
     */
    public function getWorkday()
    {
        return $this->workday;
    }

    /**
     * @param Workday $workday
     * @return ActivityTracking
     */
    public function setWorkday($workday)
    {
        $this->workday = $workday;
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
