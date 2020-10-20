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

use AppBundle\Entity\WLT\Project;

class ActivityCopy
{
    /** @var Project */
    private $project;

    /** @var bool */
    private $copyLearningProgram = true;

    public function __construct()
    {
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return ActivityCopy
     */
    public function setProject($project)
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return bool
     */
    public function getCopyLearningProgram()
    {
        return $this->copyLearningProgram;
    }

    /**
     * @param bool $copyLearningProgram
     * @return ActivityCopy
     */
    public function setCopyLearningProgram($copyLearningProgram)
    {
        $this->copyLearningProgram = $copyLearningProgram;
        return $this;
    }
}
