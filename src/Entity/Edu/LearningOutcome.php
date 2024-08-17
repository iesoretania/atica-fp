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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'edu_learning_outcome')]
#[ORM\UniqueConstraint(columns: ['subject_id', 'code'])]
class LearningOutcome implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Subject::class, inversedBy: 'learningOutcomes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Subject $subject = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private ?string $description = null;

    /**
     * @var Collection<int, Criterion>
     */
    #[ORM\OneToMany(targetEntity: Criterion::class, mappedBy: 'learningOutcome')]
    private Collection $criteria;

    public function __construct()
    {
        $this->criteria = new ArrayCollection();
    }

    public function __toString(): string
    {
        return ($this->getSubject() instanceof Subject)
            ? ($this->getSubject()->getCode() ?: $this->getSubject()->getName()) . ' - '. $this->getCode()
            : '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(Subject $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
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

    /**
     * @return Collection<int, Criterion>
     */
    public function getCriteria(): Collection
    {
        return $this->criteria;
    }
}
