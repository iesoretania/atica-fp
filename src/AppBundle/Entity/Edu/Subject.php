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

namespace AppBundle\Entity\Edu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Edu\SubjectRepository")
 * @ORM\Table(name="edu_subject")
 */
class Subject
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Grade")
     * @ORM\JoinColumn(nullable=false)
     * @var Grade
     */
    private $grade;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $internalCode;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="LearningOutcome", mappedBy="subject")
     * @ORM\OrderBy({"code": "ASC"})
     * @var LearningOutcome[]
     */
    private $learningOutcomes;

    /**
     * @ORM\OneToMany(targetEntity="Teaching", mappedBy="subject")
     * @var Teaching[]
     */
    private $teachings;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $workplaceTraining;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $workLinked;

    public function __construct()
    {
        $this->learningOutcomes = new ArrayCollection();
        $this->teachings = new ArrayCollection();

        $this->workLinked = false;
        $this->workplaceTraining = false;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Grade
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param Grade $grade
     * @return Subject
     */
    public function setGrade($grade)
    {
        $this->grade = $grade;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return Subject
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
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
     * @return Subject
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
     * @return Subject
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return LearningOutcome[]
     */
    public function getLearningOutcomes()
    {
        return $this->learningOutcomes;
    }

    /**
     * @param LearningOutcome[] $learningOutcomes
     * @return Subject
     */
    public function setLearningOutcomes($learningOutcomes)
    {
        $this->learningOutcomes = $learningOutcomes;
        return $this;
    }

    /**
     * @return Teaching[]
     */
    public function getTeachings()
    {
        return $this->teachings;
    }

    /**
     * @param Teaching[] $teachings
     * @return Subject
     */
    public function setTeachings($teachings)
    {
        $this->teachings = $teachings;
        return $this;
    }

    /**
     * @return bool
     */
    public function isWorkplaceTraining()
    {
        return $this->workplaceTraining;
    }

    /**
     * @param bool $workplaceTraining
     * @return Subject
     */
    public function setWorkplaceTraining($workplaceTraining)
    {
        $this->workplaceTraining = $workplaceTraining;
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
     * @return Subject
     */
    public function setWorkLinked($workLinked)
    {
        $this->workLinked = $workLinked;
        return $this;
    }
}
