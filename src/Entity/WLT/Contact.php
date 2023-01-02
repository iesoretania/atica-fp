<?php
/*
  Copyright (C) 2018-2023: Luis Ramón López López

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

use App\Entity\Edu\ContactMethod;
use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Workcenter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WLT\ContactRepository")
 * @ORM\Table(name="wlt_contact")
 */
class Contact
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $dateTime;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\Teacher")
     * @ORM\JoinColumn(nullable=false)
     * @var ?Teacher
     */
    private $teacher;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Workcenter")
     * @ORM\JoinColumn(nullable=false)
     * @var ?Workcenter
     */
    private $workcenter;

    /**
     * @ORM\ManyToMany(targetEntity="Project")
     * @ORM\JoinTable(name="wlt_contact_project")
     * @var Project[]
     */
    private $projects;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Edu\StudentEnrollment", fetch="EAGER")
     * @ORM\JoinTable(name="wlt_contact_student_enrollment")
     * @var StudentEnrollment[]
     */
    private $studentEnrollments;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var ?string
     */
    private $detail;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\ContactMethod")
     * @ORM\JoinColumn(nullable=true)
     * @var ?ContactMethod
     */
    private $method;

    public function __construct()
    {
        $this->projects = new ArrayCollection();
        $this->studentEnrollments = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param \DateTime $dateTime
     * @return Contact
     */
    public function setDateTime(\DateTimeInterface $dateTime)
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    /**
     * @return ?Teacher
     */
    public function getTeacher()
    {
        return $this->teacher;
    }

    /**
     * @param Teacher $teacher
     * @return Contact
     */
    public function setTeacher(Teacher $teacher)
    {
        $this->teacher = $teacher;
        return $this;
    }

    /**
     * @return ?Workcenter
     */
    public function getWorkcenter()
    {
        return $this->workcenter;
    }

    /**
     * @param Workcenter $workcenter
     * @return Contact
     */
    public function setWorkcenter(Workcenter $workcenter)
    {
        $this->workcenter = $workcenter;
        return $this;
    }

    /**
     * @return Project[]
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @param Project[] $projects
     * @return Contact
     */
    public function setProjects($projects)
    {
        $this->projects = $projects;
        return $this;
    }

    /**
     * @return StudentEnrollment[]
     */
    public function getStudentEnrollments()
    {
        return $this->studentEnrollments;
    }

    /**
     * @param StudentEnrollment[] $studentEnrollments
     * @return Contact
     */
    public function setStudentEnrollments($studentEnrollments)
    {
        $this->studentEnrollments = $studentEnrollments;
        return $this;
    }

    /**
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * @param string $detail
     * @return Contact
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }

    /**
     * @return ContactMethod|null
     */
    public function getMethod(): ?ContactMethod
    {
        return $this->method;
    }

    /**
     * @param ContactMethod|null $method
     * @return Contact
     */
    public function setMethod(?ContactMethod $method): Contact
    {
        $this->method = $method;
        return $this;
    }
}
