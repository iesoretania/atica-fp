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

namespace App\Entity\WPT;

use App\Entity\Edu\StudentEnrollment;
use App\Entity\Edu\Teacher;
use App\Entity\Workcenter;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WPT\VisitRepository")
 * @ORM\Table(name="wpt_visit")
 */
class Visit
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var ?int
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
     * @var Teacher
     */
    private $teacher;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Workcenter")
     * @ORM\JoinColumn(nullable=false)
     * @var Workcenter
     */
    private $workcenter;

    /**
     * @ORM\ManyToMany(targetEntity="Agreement")
     * @ORM\JoinTable(name="wpt_visit_agreement")
     * @var Agreement[]|Collection
     */
    private $agreements;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Edu\StudentEnrollment", fetch="EAGER")
     * @ORM\JoinTable(name="wpt_visit_student_enrollment")
     * @var StudentEnrollment[]|Collection
     */
    private $studentEnrollments;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $detail;

    public function __construct()
    {
        $this->agreements = new ArrayCollection();
        $this->studentEnrollments = new ArrayCollection();
    }

    /**
     * @return ?int
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
     * @return Visit
     */
    public function setDateTime(\DateTimeInterface $dateTime)
    {
        $this->dateTime = $dateTime;
        return $this;
    }

    /**
     * @return Teacher
     */
    public function getTeacher()
    {
        return $this->teacher;
    }

    /**
     * @param Teacher $teacher
     * @return Visit
     */
    public function setTeacher(Teacher $teacher)
    {
        $this->teacher = $teacher;
        return $this;
    }

    /**
     * @return Workcenter
     */
    public function getWorkcenter()
    {
        return $this->workcenter;
    }

    /**
     * @param Workcenter $workcenter
     * @return Visit
     */
    public function setWorkcenter(Workcenter $workcenter)
    {
        $this->workcenter = $workcenter;
        return $this;
    }

    /**
     * @return Agreement[]|Collection
     */
    public function getAgreements()
    {
        return $this->agreements;
    }

    /**
     * @param Agreement[]|Collection $agreements
     * @return Visit
     */
    public function setAgreements($agreements)
    {
        $this->agreements = $agreements;
        return $this;
    }

    /**
     * @return StudentEnrollment[]|Collection
     */
    public function getStudentEnrollments()
    {
        return $this->studentEnrollments;
    }

    /**
     * @param StudentEnrollment[]|Collection $studentEnrollments
     * @return Visit
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
     * @return Visit
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }
}
