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

namespace AppBundle\Entity\Edu;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="edu_learning_outcome",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"subject_id", "code"})}))))
 */
class LearningOutcome
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Subject", inversedBy="learningOutcomes")
     * @ORM\JoinColumn(nullable=false)
     * @var Subject
     */
    private $subject;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @var string
     */
    private $description;

    public function __toString()
    {
        return ($this->getSubject()->getCode() ?: $this->getSubject()->getName()) . ' - '. $this->getCode();
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
     * @return LearningOutcome
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
     * @return LearningOutcome
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
     * @return LearningOutcome
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
}
