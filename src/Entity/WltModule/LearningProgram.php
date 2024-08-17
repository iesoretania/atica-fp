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

namespace App\Entity\WltModule;

use App\Entity\Company;
use App\Repository\WltModule\LearningProgramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LearningProgramRepository::class)]
#[ORM\Table(name: 'wlt_learning_program')]
class LearningProgram implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    /**
     * @var Collection<int, ActivityRealization>
     */
    #[ORM\ManyToMany(targetEntity: ActivityRealization::class)]
    #[ORM\JoinTable('wlt_learning_program_activity_realization')]
    private Collection $activityRealizations;

    public function __construct()
    {
        $this->activityRealizations = new ArrayCollection();
    }

    public function __toString(): string
    {
        return (!$this->getCompany() instanceof Company || !$this->getProject() instanceof Project)
            ? ''
            : $this->getCompany()->__toString() . ' - ' . $this->getProject()->__toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): static
    {
        $this->company = $company;
        return $this;
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

    /**
     * @return Collection<int, ActivityRealization>
     */
    public function getActivityRealizations(): Collection
    {
        return $this->activityRealizations;
    }

    public function setActivityRealizations(Collection $activityRealizations): static
    {
        $this->activityRealizations = $activityRealizations;
        return $this;
    }
}
