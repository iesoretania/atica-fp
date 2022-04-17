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

namespace App\Entity\WLT;

use App\Entity\AnsweredSurvey;
use App\Entity\Edu\AcademicYear;
use App\Entity\Person;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="wlt_work_tutor_answered_survey")
 */
class WorkTutorAnsweredSurvey
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumn(nullable=false)
     * @var Project
     */
    private $project;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\AcademicYear")
     * @ORM\JoinColumn(nullable=false)
     * @var AcademicYear
     */
    private $academicYear;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(nullable=false)
     * @var Person
     */
    private $workTutor;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\AnsweredSurvey")
     * @ORM\JoinColumn(nullable=false)
     * @var AnsweredSurvey
     */
    private $answeredSurvey;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return self
     */
    public function setProject($project)
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return AcademicYear
     */
    public function getAcademicYear()
    {
        return $this->academicYear;
    }

    /**
     * @param AcademicYear $academicYear
     * @return self
     */
    public function setAcademicYear($academicYear)
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    /**
     * @return Person
     */
    public function getWorkTutor()
    {
        return $this->workTutor;
    }

    /**
     * @param Person $workTutor
     * @return self
     */
    public function setWorkTutor($workTutor)
    {
        $this->workTutor = $workTutor;
        return $this;
    }

    /**
     * @return AnsweredSurvey
     */
    public function getAnsweredSurvey()
    {
        return $this->answeredSurvey;
    }

    /**
     * @param AnsweredSurvey $answeredSurvey
     * @return self
     */
    public function setAnsweredSurvey($answeredSurvey)
    {
        $this->answeredSurvey = $answeredSurvey;
        return $this;
    }
}
