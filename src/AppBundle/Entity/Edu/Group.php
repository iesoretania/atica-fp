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
 * @ORM\Entity
 * @ORM\Table(name="edu_group")
 */
class Group
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
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher")
     * @ORM\JoinColumn(nullable=true)
     * @var Teacher
     */
    private $tutor;

    /**
     * @ORM\OneToMany(targetEntity="Teaching", mappedBy="group")
     * @var Teaching[]
     */
    private $teachings;

    /**
     * @ORM\OneToMany(targetEntity="StudentEnrollment", mappedBy="group")
     * @var StudentEnrollment[]
     */
    private $enrollments;

    public function __construct()
    {
        $this->teachings = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
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
     * @return Group
     */
    public function setGrade(Grade $grade)
    {
        $this->grade = $grade;
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
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Teacher
     */
    public function getTutor()
    {
        return $this->tutor;
    }

    /**
     * @param Teacher $tutor
     * @return Group
     */
    public function setTutor(Teacher $tutor = null)
    {
        $this->tutor = $tutor;
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
     * @return StudentEnrollment[]
     */
    public function getEnrollments()
    {
        return $this->enrollments;
    }
}
