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

use App\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'edu_teacher')]
#[ORM\UniqueConstraint(columns: ['person_id', 'academic_year_id'])]
class Teacher implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Person $person = null;

    #[ORM\ManyToOne(targetEntity: AcademicYear::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?AcademicYear $academicYear = null;

    #[ORM\ManyToOne(targetEntity: Department::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Department $department = null;

    /**
     * @var Collection<int, Teaching>
     */
    #[ORM\OneToMany(targetEntity: Teaching::class, mappedBy: 'teacher')]
    private Collection $teachings;

    public function __construct()
    {
        $this->teachings = new ArrayCollection();
    }

    public function __toString(): string
    {
        return ($this->getPerson() instanceof Person) ? $this->getPerson()->__toString() : '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPerson(): ?Person
    {
        return $this->person;
    }

    public function setPerson(Person $person): static
    {
        $this->person = $person;
        return $this;
    }

    public function getAcademicYear(): ?AcademicYear
    {
        return $this->academicYear;
    }

    public function setAcademicYear(AcademicYear $academicYear): static
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department = null): static
    {
        $this->department = $department;
        return $this;
    }

    /**
     * @return Collection<int, Teaching>
     */
    public function getTeachings(): Collection
    {
        return $this->teachings;
    }
}
