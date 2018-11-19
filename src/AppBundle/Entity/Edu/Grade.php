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
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Edu\GradeRepository")
 * @ORM\Table(name="edu_grade")
 */
class Grade
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Training")
     * @ORM\JoinColumn(nullable=false)
     * @var Training
     */
    private $training;

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
     * @return Training
     */
    public function getTraining()
    {
        return $this->training;
    }

    /**
     * @param Training $training
     * @return Grade
     */
    public function setTraining(Training $training)
    {
        $this->training = $training;
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
     * @return Grade
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
     * @return Grade
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
