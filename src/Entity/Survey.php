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

namespace App\Entity;

use App\Repository\SurveyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SurveyRepository::class)]
#[ORM\Table(name: 'survey')]
class Survey
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private ?string $title = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $startTimestamp = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $endTimestamp = null;

    /**
     * @var Collection<int, SurveyQuestion>
     */
    #[ORM\OneToMany(targetEntity: SurveyQuestion::class, mappedBy: 'survey')]
    private Collection $questions;

    /**
     * @var Collection<int, AnsweredSurvey>
     */
    #[ORM\OneToMany(targetEntity: AnsweredSurvey::class, mappedBy: 'survey', fetch: 'EXTRA_LAZY')]
    private Collection $answers;

    public function __construct()
    {
        $this->questions = new ArrayCollection();
        $this->answers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): static
    {
        $this->organization = $organization;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getStartTimestamp(): ?\DateTimeInterface
    {
        return $this->startTimestamp;
    }

    public function setStartTimestamp(?\DateTimeInterface $startTimestamp): static
    {
        $this->startTimestamp = $startTimestamp;
        return $this;
    }

    public function getEndTimestamp(): ?\DateTimeInterface
    {
        return $this->endTimestamp;
    }

    public function setEndTimestamp(?\DateTimeInterface $endTimestamp): static
    {
        $this->endTimestamp = $endTimestamp;
        return $this;
    }

    /**
     * @return Collection<int, SurveyQuestion>
     */
    public function getQuestions(): Collection
    {
        return $this->questions;
    }

    /**
     * @return Collection<int, AnsweredSurvey>
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }
}
