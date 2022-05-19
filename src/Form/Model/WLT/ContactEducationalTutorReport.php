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

namespace App\Form\Model\WLT;

use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\Teacher;
use App\Entity\WLT\Project;
use App\Entity\Workcenter;

class ContactEducationalTutorReport
{
    /** @var Teacher */
    private $teacher;

    /** @var ?Workcenter */
    private $workcenter;

    /** @var ?Project[] */
    private $projects;

    /** @var ContactMethod[] */
    private $contactMethods;

    public function __construct()
    {
    }

    /**
     * @return ?Teacher
     */
    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    /**
     * @param Teacher $teacher
     * @return ContactEducationalTutorReport
     */
    public function setTeacher(Teacher $teacher): ContactEducationalTutorReport
    {
        $this->teacher = $teacher;
        return $this;
    }

    /**
     * @return Workcenter|null
     */
    public function getWorkcenter(): ?Workcenter
    {
        return $this->workcenter;
    }

    /**
     * @param Workcenter|null $workcenter
     * @return ContactEducationalTutorReport
     */
    public function setWorkcenter(?Workcenter $workcenter): ContactEducationalTutorReport
    {
        $this->workcenter = $workcenter;
        return $this;
    }

    /**
     * @return Project[]|null
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @param Project[]|null $projects
     * @return ContactEducationalTutorReport
     */
    public function setProjects($projects): ContactEducationalTutorReport
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
     * @return ContactEducationalTutorReport
     */
    public function setContactMethods($contactMethods): ContactEducationalTutorReport
    {
        $this->contactMethods = $contactMethods;
        return $this;
    }
}
