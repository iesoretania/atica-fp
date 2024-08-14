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

namespace App\Entity\WPT;

use App\Entity\Edu\Teacher;
use App\Entity\Edu\TravelRoute;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'wpt_travel_expense')]
class TravelExpense
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $fromDateTime = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $toDateTime = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Teacher $teacher = null;

    #[ORM\ManyToOne(targetEntity: TravelRoute::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?TravelRoute $travelRoute = null;

    /**
     * @var Collection<int, Agreement>
     */
    #[ORM\ManyToMany(targetEntity: Agreement::class)]
    #[ORM\JoinTable(name: 'wpt_travel_expense_agreement')]
    private Collection $agreements;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $otherExpensesDescription = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false, options: ['default' => 0])]
    private ?int $otherExpenses = 0;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $description = null;

    public function __construct()
    {
        $this->agreements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFromDateTime(): ?\DateTimeInterface
    {
        return $this->fromDateTime;
    }

    public function setFromDateTime(\DateTimeInterface $fromDateTime): static
    {
        $this->fromDateTime = $fromDateTime;
        return $this;
    }

    public function getToDateTime(): ?\DateTimeInterface
    {
        return $this->toDateTime;
    }

    public function setToDateTime(\DateTimeInterface $toDateTime): static
    {
        $this->toDateTime = $toDateTime;
        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(Teacher $teacher): static
    {
        $this->teacher = $teacher;
        return $this;
    }

    public function getTravelRoute(): ?TravelRoute
    {
        return $this->travelRoute;
    }

    public function setTravelRoute(TravelRoute $travelRoute): static
    {
        $this->travelRoute = $travelRoute;
        return $this;
    }

    /**
     * @return Collection<int, Agreement>
     */
    public function getAgreements(): Collection
    {
        return $this->agreements;
    }

    public function setAgreements(Collection $agreements): static
    {
        $this->agreements = $agreements;
        return $this;
    }

    public function getOtherExpensesDescription(): ?string
    {
        return $this->otherExpensesDescription;
    }

    public function setOtherExpensesDescription(?string $otherExpensesDescription): static
    {
        $this->otherExpensesDescription = $otherExpensesDescription;
        return $this;
    }

    public function getOtherExpenses(): ?int
    {
        return $this->otherExpenses;
    }

    public function setOtherExpenses(?int $otherExpenses): static
    {
        $this->otherExpenses = $otherExpenses;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }
}
