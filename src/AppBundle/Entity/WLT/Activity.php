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

namespace AppBundle\Entity\WLT;

use AppBundle\Entity\Edu\Competency;
use AppBundle\Entity\Edu\Subject;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WLT\ActivityRepository")
 * @ORM\Table(name="wlt_activity",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"subject_id", "code"})}))))
 */
class Activity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\Subject")
     * @ORM\JoinColumn(nullable=false)
     * @var Subject
     */
    private $subject;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $priorLearning;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Edu\Competency")
     * @ORM\JoinTable(name="wlt_activity_competency")
     * @ORM\OrderBy({"code": "ASC"})
     * @var Competency[]
     */
    private $competencies;

    public function __construct()
    {
        $this->competencies = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getCode() . ': ' . $this->getDescription();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param Subject $subject
     * @return Activity
     */
    public function setSubject(Subject $subject)
    {
        $this->subject = $subject;
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
     * @return Activity
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Activity
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getPriorLearning()
    {
        return $this->priorLearning;
    }

    /**
     * @param string $priorLearning
     * @return Activity
     */
    public function setPriorLearning($priorLearning)
    {
        $this->priorLearning = $priorLearning;
        return $this;
    }

    /**
     * @return Competency[]|Collection
     */
    public function getCompetencies()
    {
        return $this->competencies;
    }

    /**
     * @param Competency[] $competencies
     * @return Activity
     */
    public function setCompetencies($competencies)
    {
        $this->competencies = $competencies;
        return $this;
    }
}
