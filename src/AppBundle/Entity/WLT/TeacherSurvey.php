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

use AppBundle\Entity\AnsweredSurvey;
use AppBundle\Entity\Edu\Teacher;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WLT\TeacherSurveyRepository")
 * @ORM\Table(name="wlt_teacher_survey",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"teacher_id", "answered_survey_id"})}))))
 */
class TeacherSurvey
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\Teacher")
     * @ORM\JoinColumn(nullable=false)
     * @var Teacher
     */
    private $teacher;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\AnsweredSurvey", cascade={})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @var AnsweredSurvey
     */
    private $answeredSurvey;

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
     * @return TeacherSurvey
     */
    public function setTeacher(Teacher $teacher)
    {
        $this->teacher = $teacher;
        return $this;
    }

    /**
     * @return AnsweredSurvey
     */
    public function getAnsweredSurvey()
    {
        return $this->answeredSurvey;
    }

    /**
     * @param AnsweredSurvey $answeredSurvey
     * @return TeacherSurvey
     */
    public function setAnsweredSurvey(AnsweredSurvey $answeredSurvey)
    {
        $this->answeredSurvey = $answeredSurvey;
        return $this;
    }
}
