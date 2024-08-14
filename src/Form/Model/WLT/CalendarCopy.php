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

namespace App\Form\Model\WLT;

use App\Entity\WLT\Agreement;

class CalendarCopy
{
    public const OVERWRITE_ACTION_REPLACE = 1;
    public const OVERWRITE_ACTION_ADD = 2;

    private ?Agreement $agreement;

    private int $overwriteAction = self::OVERWRITE_ACTION_REPLACE;

    public function getAgreement(): ?Agreement
    {
        return $this->agreement;
    }

    public function setAgreement(Agreement $agreement): static
    {
        $this->agreement = $agreement;
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
}
