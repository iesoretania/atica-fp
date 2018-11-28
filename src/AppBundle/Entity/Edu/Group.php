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

use AppBundle\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Edu\GroupRepository")
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
     * @ORM\ManyToOne(targetEntity="Grade", inversedBy="groups")
     * @ORM\JoinColumn(nullable=false)
     * @var Grade
     */
    private $grade;

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
     * @ORM\OneToMany(targetEntity="Teaching", mappedBy="group")
     * @var Teaching[]
     */
    private $teachings;

    /**
     * @ORM\OneToMany(targetEntity="StudentEnrollment", mappedBy="group")
     * @var StudentEnrollment[]
     */
    private $enrollments;

    /**
     * @ORM\ManyToMany(targetEntity="Teacher")
     * @ORM\JoinTable(name="edu_group_tutor")
     * @var Teacher[]
     */
    private $tutors;

    public function __toString()
    {
        return $this->getName();
    }


    public function __construct()
    {
        $this->teachings = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->tutors = new ArrayCollection();
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
    public function getInternalCode()
    {
        return $this->internalCode;
    }

    /**
     * @param string $internalCode
     * @return Group
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
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;
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

    /**
     * @return Teacher[]|Collection
     */
    public function getTutors()
    {
        return $this->tutors;
    }

    /**
     * @param Teacher[] $tutors
     * @return Group
     */
    public function setTutors($tutors)
    {
        $this->tutors = $tutors;
        return $this;
    }

    /**
     * @return Person[]
     */
    public function getStudents()
    {
        $students = [];
        foreach ($this->enrollments as $enrollment) {
            $students[] = $enrollment->getPerson();
        }
        return $students;
    }
}
