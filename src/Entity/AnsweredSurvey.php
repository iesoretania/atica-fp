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

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AnsweredSurveyRepository")
 * @ORM\Table(name="answered_survey")
 */
class AnsweredSurvey
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Survey", inversedBy="answers")
     * @var Survey
     */
    private $survey;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $timestamp;

    /**
     * @ORM\OneToMany(targetEntity="AnsweredSurveyQuestion", mappedBy="answeredSurvey")
     * @var AnsweredSurveyQuestion[]|Collection
     */
    private $answers;

    public function __construct()
    {
        $this->answers = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Survey
     */
    public function getSurvey()
    {
        return $this->survey;
    }

    /**
     * @param Survey $survey
     * @return AnsweredSurvey
     */
    public function setSurvey(Survey $survey)
    {
        $this->survey = $survey;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * @param \DateTime $timestamp
     * @return AnsweredSurvey
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * @return AnsweredSurveyQuestion[]|Collection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param AnsweredSurveyQuestion[]|Collection $answers
     * @return AnsweredSurvey
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;
        return $this;
    }
}
