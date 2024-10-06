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

namespace App\Entity\ItpModule;

use App\Entity\Edu\Group;
use App\Entity\Edu\Teacher;
use App\Repository\ItpModule\ProgramGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramGroupRepository::class)]
#[ORM\Table(name: 'itp_program_group')]
class ProgramGroup
{
    public const MODE_INHERITED = 0;
    public const MODE_GENERAL = 1;
    public const MODE_INTENSIVE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'trainingProgramGroups')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProgramGrade $programGrade = null;

    #[ORM\ManyToOne(targetEntity: Group::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Group $group = null;

    /**
     * @var Collection<int, Teacher>
     */
    #[ORM\ManyToMany(targetEntity: Teacher::class)]
    #[ORM\JoinTable(name: 'itp_program_group_manager')]
    private Collection $managers;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private ?bool $locked = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::INTEGER, nullable: false)]
    private ?int $modality = self::MODE_GENERAL;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $targetHours = null;

    /**
     * @var Collection<int, StudentLearningProgram>
     */
    #[ORM\OneToMany(targetEntity: StudentLearningProgram::class, mappedBy: 'trainingProgramGroup', orphanRemoval: true)]
    private Collection $studentLearningPrograms;

    public function __construct()
    {
        $this->managers = new ArrayCollection();
        $this->studentLearningPrograms = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProgramGrade(): ?ProgramGrade
    {
        return $this->programGrade;
    }

    public function setProgramGrade(ProgramGrade $programGrade): static
    {
        $this->programGrade = $programGrade;

        return $this;
    }

    public function getGroup(): ?Group
    {
        return $this->group;
    }

    public function setGroup(Group $group): ProgramGroup
    {
        $this->group = $group;
        return $this;
    }

    /**
     * @return Collection<int, Teacher>
     */
    public function getManagers(): Collection
    {
        return $this->managers;
    }

    public function addManager(Teacher $manager): static
    {
        if (!$this->managers->contains($manager)) {
            $this->managers->add($manager);
        }

        return $this;
    }

    public function removeManager(Teacher $manager): static
    {
        $this->managers->removeElement($manager);

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

    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked): static
    {
        $this->locked = $locked;

        return $this;
    }

    public function getModality(): ?int
    {
        return $this->modality;
    }

    public function getActualModality(): ?int
    {
        return $this->modality === self::MODE_INHERITED ?
            $this->getProgramGrade()->getTrainingProgram()->getDefaultModality() :
            $this->modality;
    }

    public function setModality(int $modality): static
    {
        $this->modality = $modality;

        return $this;
    }

    public function getTargetHours(): ?int
    {
        return $this->targetHours;
    }

    public function setTargetHours(?int $targetHours): static
    {
        $this->targetHours = $targetHours;

        return $this;
    }

    /**
     * @return Collection<int, StudentLearningProgram>
     */
    public function getStudentLearningPrograms(): Collection
    {
        return $this->studentLearningPrograms;
    }
}
