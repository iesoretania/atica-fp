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

namespace App\Form\Model\ItpModule;

use App\Entity\ItpModule\StudentProgramWorkcenter;

class CalendarCopy
{
    public const OVERWRITE_ACTION_REPLACE = 1;
    public const OVERWRITE_ACTION_ADD = 2;

    private ?StudentProgramWorkcenter $sourceStudentProgramWorkcenter = null;

    private int $overwriteAction = self::OVERWRITE_ACTION_REPLACE;

    public function getSourceStudentProgramWorkcenter(): ?StudentProgramWorkcenter
    {
        return $this->sourceStudentProgramWorkcenter;
    }

    public function setSourceStudentProgramWorkcenter(StudentProgramWorkcenter $sourceStudentProgramWorkcenter): static
    {
        $this->sourceStudentProgramWorkcenter = $sourceStudentProgramWorkcenter;
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
