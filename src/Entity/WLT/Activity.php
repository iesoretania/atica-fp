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

namespace App\Entity\WLT;

use App\Entity\Edu\Competency;
use App\Repository\WLT\ActivityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'wlt_activity')]
#[ORM\UniqueConstraint(columns: ['project_id', 'code'])]
class Activity implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'activities')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $priorLearning = null;

    /**
     * @var Collection<int, Competency>
     */
    #[ORM\ManyToMany(targetEntity: Competency::class)]
    #[ORM\JoinTable(name: 'wlt_activity_competency')]
    #[ORM\OrderBy(['code' => Criteria::ASC])]
    private Collection $competencies;

    /**
     * @var Collection<int, ActivityRealization>
     */
    #[ORM\OneToMany(targetEntity: ActivityRealization::class, mappedBy: 'activity')]
    private Collection $activityRealizations;

    public function __construct()
    {
        $this->competencies = new ArrayCollection();
        $this->activityRealizations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCode() . ': ' . $this->getDescription();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;
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

    public function getPriorLearning(): ?string
    {
        return $this->priorLearning;
    }

    public function setPriorLearning(?string $priorLearning): static
    {
        $this->priorLearning = $priorLearning;
        return $this;
    }

    /**
     * @return Collection<int, Competency>
     */
    public function getCompetencies(): Collection
    {
        return $this->competencies;
    }

    public function setCompetencies(Collection $competencies): static
    {
        $this->competencies = $competencies;
        return $this;
    }

    /**
     * @return Collection<int, ActivityRealization>
     */
    public function getActivityRealizations(): Collection
    {
        return $this->activityRealizations;
    }
}
