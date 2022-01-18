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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AnsweredSurveyQuestionRepository")
 * @ORM\Table(name="answered_survey_question",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"survey_question_id", "answered_survey_id"})}))))
 */
class AnsweredSurveyQuestion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AnsweredSurvey", inversedBy="answers")
     * @ORM\JoinColumn(nullable=false)
     * @var AnsweredSurvey
     */
    private $answeredSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="SurveyQuestion")
     * @ORM\JoinColumn(nullable=false)
     * @var SurveyQuestion
     */
    private $surveyQuestion;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $textValue;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    private $numericValue;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return AnsweredSurveyQuestion
     */
    public function setAnsweredSurvey(AnsweredSurvey $answeredSurvey)
    {
        $this->answeredSurvey = $answeredSurvey;
        return $this;
    }

    /**
     * @return SurveyQuestion
     */
    public function getSurveyQuestion()
    {
        return $this->surveyQuestion;
    }

    /**
     * @param SurveyQuestion $surveyQuestion
     * @return AnsweredSurveyQuestion
     */
    public function setSurveyQuestion(SurveyQuestion $surveyQuestion)
    {
        $this->surveyQuestion = $surveyQuestion;
        return $this;
    }

    /**
     * @return string
     */
    public function getTextValue()
    {
        return $this->textValue;
    }

    /**
     * @param string $textValue
     * @return AnsweredSurveyQuestion
     */
    public function setTextValue($textValue)
    {
        $this->textValue = $textValue;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumericValue()
    {
        return $this->numericValue;
    }

    /**
     * @param int $numericValue
     * @return AnsweredSurveyQuestion
     */
    public function setNumericValue($numericValue)
    {
        $this->numericValue = $numericValue;
        return $this;
    }
}
