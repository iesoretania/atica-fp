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

namespace App\Entity\WLT;

use App\Entity\Edu\LearningOutcome;
use App\Repository\WLT\ActivityRealizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityRealizationRepository::class)]
#[ORM\Table(name: 'wlt_activity_realization')]
#[ORM\UniqueConstraint(columns: ['activity_id', 'code'])]
class ActivityRealization implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Activity::class, inversedBy: 'activityRealizations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Activity $activity = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $code = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    /**
     * @var Collection<int, LearningOutcome>
     */
    #[ORM\ManyToMany(targetEntity: LearningOutcome::class)]
    #[ORM\JoinTable(name: 'wlt_activity_realization_learning_outcome')]
    private Collection $learningOutcomes;

    public function __construct()
    {
        $this->learningOutcomes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getCode() . ': ' . $this->getDescription();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getActivity(): ?Activity
    {
        return $this->activity;
    }

    public function setActivity(Activity $activity): static
    {
        $this->activity = $activity;
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
     * @return Collection<int, LearningOutcome>
     */
    public function getLearningOutcomes(): Collection
    {
        return $this->learningOutcomes;
    }

    public function setLearningOutcomes(Collection $learningOutcomes): static
    {
        $this->learningOutcomes = $learningOutcomes;
        return $this;
    }

    public function getSubjectLearningOutcomes(): array
    {
        $data = [];

        foreach ($this->getLearningOutcomes() as $learningOutcome) {
            $subject = $learningOutcome->getSubject();
            $data[$subject->getCode() ?: $subject->getName() ][] = $learningOutcome;
        }

        return $data;
    }
}
