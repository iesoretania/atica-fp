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

namespace App\Entity\Edu;

use App\Entity\Person;
use App\Repository\Edu\GroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GroupRepository::class)]
#[ORM\Table(name: 'edu_group')]
class Group implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Grade::class, inversedBy: 'groups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Grade $grade = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $internalCode = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    /**
     * @var Collection<int, Teaching>
     */
    #[ORM\OneToMany(targetEntity: Teaching::class, mappedBy: 'group')]
    private Collection $teachings;

    /**
     * @var Collection<int, StudentEnrollment>
     */
    #[ORM\OneToMany(targetEntity: StudentEnrollment::class, mappedBy: 'group')]
    private Collection $enrollments;

    /**
     * @var Collection<int, Teacher>
     */
    #[ORM\ManyToMany(targetEntity: Teacher::class)]
    #[ORM\JoinTable(name: 'edu_group_tutor')]
    private Collection $tutors;

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function __construct()
    {
        $this->teachings = new ArrayCollection();
        $this->enrollments = new ArrayCollection();
        $this->tutors = new ArrayCollection();
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
     * @return Collection<int, Teaching>
     */
    public function getTeachings(): Collection
    {
        return $this->teachings;
    }

    /**
     * @return Collection<int, StudentEnrollment>
     */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    /**
     * @return Collection<int, Teacher>
     */
    public function getTutors(): Collection
    {
        return $this->tutors;
    }

    public function setTutors(Collection $tutors): static
    {
        $this->tutors = $tutors;
        return $this;
    }

    /**
     * @return Person[]
     */
    public function getStudents(): array
    {
        $students = [];
        foreach ($this->enrollments as $enrollment) {
            $students[] = $enrollment->getPerson();
        }
        return $students;
    }
}
