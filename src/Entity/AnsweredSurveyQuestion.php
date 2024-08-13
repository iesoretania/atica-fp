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

namespace App\Entity;

use App\Repository\AnsweredSurveyQuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnsweredSurveyQuestionRepository::class)]
#[ORM\Table(name: 'answered_survey_question')]
#[ORM\UniqueConstraint(columns: ['survey_question_id', 'answered_survey_id'])]
class AnsweredSurveyQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: AnsweredSurvey::class, inversedBy: 'answers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AnsweredSurvey $answeredSurvey = null;

    #[ORM\ManyToOne(targetEntity: SurveyQuestion::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?SurveyQuestion $surveyQuestion = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $textValue = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $numericValue = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAnsweredSurvey(): ?AnsweredSurvey
    {
        return $this->answeredSurvey;
    }

    public function setAnsweredSurvey(AnsweredSurvey $answeredSurvey): static
    {
        $this->answeredSurvey = $answeredSurvey;
        return $this;
    }

    public function getSurveyQuestion(): ?SurveyQuestion
    {
        return $this->surveyQuestion;
    }

    public function setSurveyQuestion(SurveyQuestion $surveyQuestion): static
    {
        $this->surveyQuestion = $surveyQuestion;
        return $this;
    }

    public function getTextValue(): ?string
    {
        return $this->textValue;
    }

    public function setTextValue(?string $textValue): static
    {
        $this->textValue = $textValue;
        return $this;
    }

    public function getNumericValue(): ?int
    {
        return $this->numericValue;
    }

    public function setNumericValue(?int $numericValue): static
    {
        $this->numericValue = $numericValue;
        return $this;
    }
}
