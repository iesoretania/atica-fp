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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
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
     */
    public function setGrade(Grade $grade)
    {
        $this->grade = $grade;
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
     */
    public function setCode($code)
    {
        $this->code = $code;
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
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return LearningOutcome[]
     */
    public function getLearningOutcomes()
    {
        return $this->learningOutcomes;
    }
}
