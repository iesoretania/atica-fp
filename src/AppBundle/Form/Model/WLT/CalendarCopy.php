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

namespace AppBundle\Form\Model\WLT;

use AppBundle\Entity\WLT\Agreement;

class CalendarCopy
{
    const OVERWRITE_ACTION_REPLACE = 1;
    const OVERWRITE_ACTION_ADD = 2;

    /** @var Agreement */
    private $agreement;

    /** @var int */
    private $overwriteAction;

    public function __construct()
    {
        $this->overwriteAction = self::OVERWRITE_ACTION_REPLACE;
    }

    /**
     * @return Agreement
     */
    public function getAgreement()
    {
        return $this->agreement;
    }

    /**
     * @param Agreement $agreement
     * @return CalendarCopy
     */
    public function setAgreement($agreement)
    {
        $this->agreement = $agreement;
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
     * @return CalendarCopy
     */
    public function setOverwriteAction($overwriteAction)
    {
        $this->overwriteAction = $overwriteAction;
        return $this;
    }
}
