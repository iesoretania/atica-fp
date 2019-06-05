<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Entity\Edu;

use AppBundle\Entity\Survey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Edu\TrainingRepository")
 * @ORM\Table(name="edu_training")
 */
class Training
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AcademicYear")
     * @ORM\JoinColumn(nullable=false)
     * @var AcademicYear
     */
    private $academicYear;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $internalCode;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="Competency", mappedBy="training")
     * @ORM\OrderBy({"code": "ASC"})
     * @var Competency[]
     */
    private $competencies;

    /**
     * @ORM\ManyToOne(targetEntity="Department")
     * @var Department|null
     */
    private $department;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $workLinked;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $wltStudentSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $wltCompanySurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $wltTeacherSurvey;

    public function __construct()
    {
        $this->competencies = new ArrayCollection();
        $this->workLinked = false;
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getInternalCode()
    {
        return $this->internalCode;
    }

    /**
     * @param string $internalCode
     * @return Training
     */
    public function setInternalCode($internalCode)
    {
        $this->internalCode = $internalCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Training
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @return Training
     */
    public function setAcademicYear(AcademicYear $academicYear)
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    /**
     * @return Competency[]
     */
    public function getCompetencies()
    {
        return $this->competencies;
    }

    /**
     * @return Department|null
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param Department|null $department
     * @return Training
     */
    public function setDepartment($department = null)
    {
        $this->department = $department;
        return $this;
    }

    /**
     * @return bool
     */
    public function isWorkLinked()
    {
        return $this->workLinked;
    }

    /**
     * @param bool $workLinked
     * @return Training
     */
    public function setWorkLinked($workLinked)
    {
        $this->workLinked = $workLinked;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getWltStudentSurvey()
    {
        return $this->wltStudentSurvey;
    }

    /**
     * @param Survey $wltStudentSurvey
     * @return Training
     */
    public function setWltStudentSurvey($wltStudentSurvey)
    {
        $this->wltStudentSurvey = $wltStudentSurvey;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getWltCompanySurvey()
    {
        return $this->wltCompanySurvey;
    }

    /**
     * @param Survey $wltCompanySurvey
     * @return Training
     */
    public function setWltCompanySurvey($wltCompanySurvey)
    {
        $this->wltCompanySurvey = $wltCompanySurvey;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getWltTeacherSurvey()
    {
        return $this->wltTeacherSurvey;
    }

    /**
     * @param Survey $wltTeacherSurvey
     * @return Training
     */
    public function setWltTeacherSurvey($wltTeacherSurvey)
    {
        $this->wltTeacherSurvey = $wltTeacherSurvey;
        return $this;
    }
}
