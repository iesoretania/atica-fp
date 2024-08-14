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

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class CalendarAdd
{
    public const OVERWRITE_ACTION_REPLACE = 1;
    public const OVERWRITE_ACTION_ADD = 2;

    private \DateTimeInterface $startDate;

    private int $totalHours = 0;

    private int $hoursMon = 0;

    private int $hoursTue = 0;

    private int $hoursWed = 0;

    private int $hoursThu = 0;

    private int $hoursFri = 0;

    private int $hoursSat = 0;

    private int $hoursSun = 0;

    private int $overwriteAction = self::OVERWRITE_ACTION_REPLACE;

    private bool $ignoreNonWorkingDays = false;

    public function __construct()
    {
        $this->startDate = new \DateTime();
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getTotalHours(): int
    {
        return $this->totalHours;
    }

    public function setTotalHours(int $totalHours): static
    {
        $this->totalHours = $totalHours;
        return $this;
    }

    public function getHoursMon(): int
    {
        return $this->hoursMon;
    }

    public function setHoursMon(int $hoursMon): static
    {
        $this->hoursMon = $hoursMon;
        return $this;
    }

    public function getHoursTue(): int
    {
        return $this->hoursTue;
    }

    public function setHoursTue(int $hoursTue): static
    {
        $this->hoursTue = $hoursTue;
        return $this;
    }

    public function getHoursWed(): int
    {
        return $this->hoursWed;
    }

    public function setHoursWed(int $hoursWed): static
    {
        $this->hoursWed = $hoursWed;
        return $this;
    }

    public function getHoursThu(): int
    {
        return $this->hoursThu;
    }

    public function setHoursThu(int $hoursThu): static
    {
        $this->hoursThu = $hoursThu;
        return $this;
    }

    public function getHoursFri(): int
    {
        return $this->hoursFri;
    }

    public function setHoursFri(int $hoursFri): static
    {
        $this->hoursFri = $hoursFri;
        return $this;
    }

    public function getHoursSat(): int
    {
        return $this->hoursSat;
    }

    public function setHoursSat(int $hoursSat): static
    {
        $this->hoursSat = $hoursSat;
        return $this;
    }

    public function getHoursSun(): int
    {
        return $this->hoursSun;
    }

    public function setHoursSun(int $hoursSun): static
    {
        $this->hoursSun = $hoursSun;
        return $this;
    }

    public function getOverwriteAction(): int
    {
        return $this->overwriteAction;
    }

    public function setOverwriteAction(int $overwriteAction): static
    {
        $this->overwriteAction = $overwriteAction;
        return $this;
    }

    public function getIgnoreNonWorkingDays(): bool
    {
        return $this->ignoreNonWorkingDays;
    }

    public function setIgnoreNonWorkingDays(bool $ignoreNonWorkingDays): CalendarAdd
    {
        $this->ignoreNonWorkingDays = $ignoreNonWorkingDays;
        return $this;
    }

    #[Assert\GreaterThan(value: 0, message: 'calendar.week_hours.invalid')]
    public function getWeekHours(): int
    {
        return $this->getHoursMon() + $this->getHoursTue() + $this->getHoursWed() + $this->getHoursThu() +
            $this->getHoursFri() + $this->getHoursSat() + $this->getHoursSun();
    }
}
