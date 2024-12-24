<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Entity\Edu;

use App\Repository\Edu\PerformanceScaleValueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PerformanceScaleValueRepository::class)]
#[ORM\Table(name: 'edu_performance_scale_value')]
class PerformanceScaleValue implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PerformanceScale::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?PerformanceScale $performanceScale = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $description = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $numericGrade = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    public function __toString(): string
    {
        return (string) $this->getDescription();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerformanceScale(): ?PerformanceScale
    {
        return $this->performanceScale;
    }

    public function setPerformanceScale(PerformanceScale $performanceScale): static
    {
        $this->performanceScale = $performanceScale;
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

    public function getNumericGrade(): ?int
    {
        return $this->numericGrade;
    }

    public function setNumericGrade(int $numericGrade): static
    {
        $this->numericGrade = $numericGrade;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }
}
