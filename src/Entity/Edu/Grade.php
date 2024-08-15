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

use App\Repository\Edu\GradeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GradeRepository::class)]
#[ORM\Table(name: 'edu_grade')]
class Grade implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Training::class, inversedBy: 'grades')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Training $training = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $internalCode = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * @var Collection<int, Group>
     */
    #[ORM\OneToMany(targetEntity: Group::class, mappedBy: 'grade')]
    #[ORM\OrderBy(['name' => Criteria::ASC])]
    private Collection $groups;

    /**
     * @var Collection<int, Subject>
     */
    #[ORM\OneToMany(targetEntity: Subject::class, mappedBy: 'grade')]
    #[ORM\OrderBy(['name' => Criteria::ASC])]
    private Collection $subjects;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->subjects = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTraining(): ?Training
    {
        return $this->training;
    }

    public function setTraining(Training $training): static
    {
        $this->training = $training;
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
     * @return Collection<int, Group>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->subjects;
    }
}
