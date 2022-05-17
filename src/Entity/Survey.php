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
 * @ORM\Entity(repositoryClass="App\Repository\SurveyRepository")
 * @ORM\Table(name="survey")
 */
class Survey
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Organization")
     * @ORM\JoinColumn(nullable=false)
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $title;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $startTimestamp;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $endTimestamp;

    /**
     * @ORM\OneToMany(targetEntity="SurveyQuestion", mappedBy="survey")
     * @var SurveyQuestion[]|Collection
     */
    private $questions;

    /**
     * @ORM\OneToMany(targetEntity="AnsweredSurvey", mappedBy="survey", fetch="EXTRA_LAZY")
     * @var AnsweredSurvey[]|Collection
     */
    private $answers;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
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
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     * @return Survey
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return Survey
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getStartTimestamp()
    {
        return $this->startTimestamp;
    }

    /**
     * @param ?\DateTimeInterface $startTimestamp
     * @return Survey
     */
    public function setStartTimestamp(?\DateTimeInterface $startTimestamp)
    {
        $this->startTimestamp = $startTimestamp;
        return $this;
    }

    /**
     * @return ?\DateTimeInterface
     */
    public function getEndTimestamp()
    {
        return $this->endTimestamp;
    }

    /**
     * @param ?\DateTimeInterface $endTimestamp
     * @return Survey
     */
    public function setEndTimestamp(?\DateTimeInterface $endTimestamp)
    {
        $this->endTimestamp = $endTimestamp;
        return $this;
    }

    /**
     * @return SurveyQuestion[]|Collection
     */
    public function getQuestions()
    {
        return $this->questions;
    }

    /**
     * @param SurveyQuestion[]|Collection $questions
     * @return Survey
     */
    public function setQuestions($questions)
    {
        $this->questions = $questions;
        return $this;
    }

    /**
     * @return AnsweredSurvey[]|Collection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param AnsweredSurvey[]|Collection $answers
     * @return Survey
     */
    public function setAnswers($answers)
    {
        $this->answers = $answers;
        return $this;
    }
}
