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

namespace AppBundle\Entity\WLT;

use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Edu\Teacher;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WLT\MeetingRepository")
 * @ORM\Table(name="wlt_meeting")
 */
class Meeting
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumn(nullable=false)
     * @var Project
     */
    private $project;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $dateTime;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Edu\StudentEnrollment", fetch="EAGER")
     * @ORM\JoinTable(name="wlt_meeting_student_enrollment")
     * @var StudentEnrollment[]
     */
    private $studentEnrollments;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\Teacher", fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     * @var Teacher
     */
    private $createdBy;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Edu\Teacher", fetch="EAGER")
     * @ORM\JoinTable(name="wlt_meeting_teacher")
     * @var Teacher[]
     */
    private $teachers;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $detail;
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return Meeting
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
        return $this;
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
     * @return Meeting
     */
    public function setDateTime($dateTime)
    {
        $this->dateTime = $dateTime;
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
     * @return Meeting
     */
    public function setStudentEnrollments($studentEnrollments)
    {
        $this->studentEnrollments = $studentEnrollments;
        return $this;
    }

    /**
     * @return Teacher
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param Teacher $createdBy
     * @return Meeting
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Teacher[]
     */
    public function getTeachers()
    {
        return $this->teachers;
    }

    /**
     * @param Teacher[] $teachers
     * @return Meeting
     */
    public function setTeachers($teachers)
    {
        $this->teachers = $teachers;
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
     * @return Meeting
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;
        return $this;
    }
}
