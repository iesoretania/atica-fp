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
 * @ORM\Entity(repositoryClass="AppBundle\Repository\Edu\TeachingRepository")
 * @ORM\Table(name="edu_teaching",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"teacher_id", "group_id", "subject_id"})}))
 */
class Teaching
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher", inversedBy="teachings")
     * @ORM\JoinColumn(nullable=false)
     * @var Teacher
     */
    private $teacher;

    /**
     * @ORM\ManyToOne(targetEntity="Group", inversedBy="teachings")
     * @ORM\JoinColumn(nullable=false)
     * @var Group
     */
    private $group;

    /**
     * @ORM\ManyToOne(targetEntity="Subject")
     * @ORM\JoinColumn(nullable=false)
     * @var Subject
     */
    private $subject;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return Teaching
     */
    public function setTeacher($teacher)
    {
        $this->teacher = $teacher;
        return $this;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param Group $group
     * @return Teaching
     */
    public function setGroup($group)
    {
        $this->group = $group;
        return $this;
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
     * @return Teaching
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
        return $this;
    }
}
