<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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

namespace AppBundle\Entity\WLT;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="wlt_tracking",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"work_day_id", "activity_realization_id"})}))))
 */
class Tracking
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="WorkDay", inversedBy="trackings")
     * @ORM\JoinColumn(nullable=false)
     * @var WorkDay
     */
    private $workDay;

    /**
     * @ORM\ManyToOne(targetEntity="ActivityRealization")
     * @ORM\JoinColumn(nullable=false)
     * @var ActivityRealization
     */
    private $activityRealization;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return WorkDay
     */
    public function getWorkDay()
    {
        return $this->workDay;
    }

    /**
     * @param WorkDay $workDay
     * @return Tracking
     */
    public function setWorkDay(WorkDay $workDay)
    {
        $this->workDay = $workDay;
        return $this;
    }

    /**
     * @return ActivityRealization
     */
    public function getActivityRealization()
    {
        return $this->activityRealization;
    }

    /**
     * @param ActivityRealization $activityRealization
     * @return Tracking
     */
    public function setActivityRealization(ActivityRealization $activityRealization)
    {
        $this->activityRealization = $activityRealization;
        return $this;
    }
}
