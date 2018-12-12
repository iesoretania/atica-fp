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

namespace AppBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class CalendarAdd
{
    const OVERWRITE_ACTION_REPLACE = 1;
    const OVERWRITE_ACTION_ADD = 2;

    /** @var \DateTime */
    private $startDate;

    /** @var int */
    private $totalHours;

    /** @var int */
    private $hoursMon;

    /** @var int */
    private $hoursTue;

    /** @var int */
    private $hoursWed;

    /** @var int */
    private $hoursThu;

    /** @var int */
    private $hoursFri;

    /** @var int */
    private $hoursSat;

    /** @var int */
    private $hoursSun;

    /** @var int */
    private $overwriteAction;

    public function __construct()
    {
        $this->startDate = new \DateTime();
        $this->totalHours = 0;
        $this->hoursMon = 0;
        $this->hoursTue = 0;
        $this->hoursWed = 0;
        $this->hoursThu = 0;
        $this->hoursFri = 0;
        $this->hoursSat = 0;
        $this->hoursSun = 0;
        $this->overwriteAction = self::OVERWRITE_ACTION_REPLACE;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return CalendarAdd
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalHours()
    {
        return $this->totalHours;
    }

    /**
     * @param int $totalHours
     * @return CalendarAdd
     */
    public function setTotalHours($totalHours)
    {
        $this->totalHours = $totalHours;
        return $this;
    }

    /**
     * @return int
     */
    public function getHoursMon()
    {
        return $this->hoursMon;
    }

    /**
     * @param int $hoursMon
     * @return CalendarAdd
     */
    public function setHoursMon($hoursMon)
    {
        $this->hoursMon = $hoursMon;
        return $this;
    }

    /**
     * @return int
     */
    public function getHoursTue()
    {
        return $this->hoursTue;
    }

    /**
     * @param int $hoursTue
     * @return CalendarAdd
     */
    public function setHoursTue($hoursTue)
    {
        $this->hoursTue = $hoursTue;
        return $this;
    }

    /**
     * @return int
     */
    public function getHoursWed()
    {
        return $this->hoursWed;
    }

    /**
     * @param int $hoursWed
     * @return CalendarAdd
     */
    public function setHoursWed($hoursWed)
    {
        $this->hoursWed = $hoursWed;
        return $this;
    }

    /**
     * @return int
     */
    public function getHoursThu()
    {
        return $this->hoursThu;
    }

    /**
     * @param int $hoursThu
     * @return CalendarAdd
     */
    public function setHoursThu($hoursThu)
    {
        $this->hoursThu = $hoursThu;
        return $this;
    }

    /**
     * @return int
     */
    public function getHoursFri()
    {
        return $this->hoursFri;
    }

    /**
     * @param int $hoursFri
     * @return CalendarAdd
     */
    public function setHoursFri($hoursFri)
    {
        $this->hoursFri = $hoursFri;
        return $this;
    }

    /**
     * @return int
     */
    public function getHoursSat()
    {
        return $this->hoursSat;
    }

    /**
     * @param int $hoursSat
     * @return CalendarAdd
     */
    public function setHoursSat($hoursSat)
    {
        $this->hoursSat = $hoursSat;
        return $this;
    }

    /**
     * @return int
     */
    public function getHoursSun()
    {
        return $this->hoursSun;
    }

    /**
     * @param int $hoursSun
     * @return CalendarAdd
     */
    public function setHoursSun($hoursSun)
    {
        $this->hoursSun = $hoursSun;
        return $this;
    }

    /**
     * @return int
     */
    public function getOverwriteAction()
    {
        return $this->overwriteAction;
    }

    /**
     * @param int $overwriteAction
     * @return CalendarAdd
     */
    public function setOverwriteAction($overwriteAction)
    {
        $this->overwriteAction = $overwriteAction;
        return $this;
    }

    /**
     * @Assert\GreaterThan(value=0, message="calendar.week_hours.invalid")
     * @return int
     */
    public function getWeekHours()
    {
        return $this->getHoursMon() + $this->getHoursTue() + $this->getHoursWed() + $this->getHoursThu() +
            $this->getHoursFri() + $this->getHoursSat() + $this->getHoursSun();
    }
}
