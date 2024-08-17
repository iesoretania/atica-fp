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

namespace App\Entity\WptModule;

use App\Entity\Edu\Criterion;
use App\Repository\WptModule\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'wpt_activity')]
#[ORM\UniqueConstraint(columns: ['shift_id', 'code'])]
class Activity implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Shift::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Shift $shift = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /**
     * @var Collection<int, Criterion>
     */
    #[ORM\ManyToMany(targetEntity: Criterion::class)]
    #[ORM\JoinTable(name: 'wpt_activity_criterion')]
    #[ORM\OrderBy(['code' => Criteria::ASC])]
    private Collection $criteria;

    public function __construct()
    {
        $this->criteria = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCode() . ': ' . $this->getDescription();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShift(): ?Shift
    {
        return $this->shift;
    }

    public function setShift(Shift $shift): static
    {
        $this->shift = $shift;
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

    public function setCriteria(Collection $criteria): static
    {
        $this->criteria = $criteria;
        return $this;
    }
}
