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

namespace AppBundle\Form\Model\ICT;

use AppBundle\Entity\ICT\Priority;
use AppBundle\Entity\Person;

class TriageTicket
{
    /**
     * @var Person
     */
    private $assignee;

    /**
     * @var Priority
     */
    private $priority;

    /**
     * @return Person
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * @param Person $assignee
     * @return TriageTicket
     */
    public function setAssignee($assignee)
    {
        $this->assignee = $assignee;
        return $this;
    }

    /**
     * @return Priority
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param Priority $priority
     * @return TriageTicket
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }
}
