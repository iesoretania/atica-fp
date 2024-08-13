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

use App\Repository\SurveyQuestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SurveyQuestionRepository::class)]
#[ORM\Table(name: 'survey_question')]
class SurveyQuestion
{
    public const FIXED = 'fixed';
    public const TEXTFIELD = 'text';
    public const TEXTAREA = 'textarea';
    public const RANGE_0_5 = 'range_0_5';
    public const RANGE_1_5 = 'range_1_5';
    public const RANGE_0_10 = 'range_0_10';
    public const RANGE_1_10 = 'range_1_10';

    public const TYPES = [
        self::RANGE_0_5,
        self::RANGE_1_5,
        self::RANGE_0_10,
        self::RANGE_1_10,
        self::TEXTFIELD,
        self::TEXTAREA,
        self::FIXED
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Survey::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Survey $survey = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $items = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $mandatory;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $orderNr;

    public function __construct()
    {
        $this->orderNr = 0;
        $this->mandatory = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSurvey(): ?Survey
    {
        return $this->survey;
    }

    public function setSurvey(Survey $survey): static
    {
        $this->survey = $survey;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getItems(): ?string
    {
        return $this->items;
    }

    public function setItems(?string $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function isMandatory(): ?bool
    {
        return $this->mandatory;
    }

    public function setMandatory(bool $mandatory): static
    {
        $this->mandatory = $mandatory;
        return $this;
    }

    public function getOrderNr(): ?int
    {
        return $this->orderNr;
    }

    public function setOrderNr(int $orderNr): static
    {
        $this->orderNr = $orderNr;
        return $this;
    }
}
