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

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\SurveyQuestionRepository")
 * @ORM\Table(name="survey_question")
 */
class SurveyQuestion
{
    const FIXED = 'fixed';
    const TEXTFIELD = 'text';
    const TEXTAREA = 'textarea';
    const RANGE_1_5 = 'range_1_5';
    const RANGE_1_10 = 'range_1_10';

    const TYPES = [
        self::RANGE_1_5,
        self::RANGE_1_10,
        self::TEXTFIELD,
        self::TEXTAREA,
        self::FIXED
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Survey", inversedBy="questions")
     * @ORM\JoinColumn(nullable=false)
     * @var Survey
     */
    private $survey;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $items;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $mandatory;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $orderNr;

    public function __construct()
    {
        $this->orderNr = 0;
        $this->mandatory = false;
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
     * @return SurveyQuestion
     */
    public function setSurvey(Survey $survey)
    {
        $this->survey = $survey;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return SurveyQuestion
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return SurveyQuestion
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param string $items
     * @return SurveyQuestion
     */
    public function setItems($items)
    {
        $this->items = $items;
        return $this;
    }

    /**
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * @param bool $mandatory
     * @return SurveyQuestion
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = $mandatory;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrderNr()
    {
        return $this->orderNr;
    }

    /**
     * @param int $orderNr
     * @return SurveyQuestion
     */
    public function setOrderNr($orderNr)
    {
        $this->orderNr = $orderNr;
        return $this;
    }
}
