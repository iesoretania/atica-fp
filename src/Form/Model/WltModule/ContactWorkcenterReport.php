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

namespace App\Form\Model\WltModule;

use App\Entity\Edu\ContactMethod;
use App\Entity\WltModule\Project;

class ContactWorkcenterReport
{
    /** @var ?Project[] */
    private $projects;

    /** @var ?ContactMethod[] */
    private $contactMethods;

    /**
     * @return Project[]|null
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @param Project[]|null $projects
     */
    public function setProjects($projects): static
    {
        $this->projects = $projects;
        return $this;
    }

    /**
     * @return ContactMethod[]
     */
    public function getContactMethods()
    {
        return $this->contactMethods;
    }

    /**
     * @param ContactMethod[] $contactMethods
     */
    public function setContactMethods($contactMethods): static
    {
        $this->contactMethods = $contactMethods;
        return $this;
    }
}