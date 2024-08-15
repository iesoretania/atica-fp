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

use App\Repository\Edu\SubjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
#[ORM\Table(name: 'edu_subject')]
class Subject implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Grade::class, inversedBy: 'subjects')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Grade $grade = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $internalCode = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $name = null;

    /**
     * @var Collection<int, LearningOutcome>
     */
    #[ORM\OneToMany(targetEntity: LearningOutcome::class, mappedBy: 'subject')]
    #[ORM\OrderBy(['code' => Criteria::ASC])]
    private Collection $learningOutcomes;

    /**
     * @var Collection<int, Teaching>
     */
    #[ORM\OneToMany(targetEntity: Teaching::class, mappedBy: 'subject')]
    private Collection $teachings;

    public function __construct()
    {
        $this->learningOutcomes = new ArrayCollection();
        $this->teachings = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGrade(): ?Grade
    {
        return $this->grade;
    }

    public function setGrade(Grade $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getInternalCode(): ?string
    {
        return $this->internalCode;
    }

    public function setInternalCode(?string $internalCode): static
    {
        $this->internalCode = $internalCode;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Collection<int, LearningOutcome>
     */
    public function getLearningOutcomes(): Collection
    {
        return $this->learningOutcomes;
    }

    /**
     * @return Collection<int, Teaching>
     */
    public function getTeachings(): Collection
    {
        return $this->teachings;
    }
}
