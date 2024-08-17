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

use App\Entity\Person;
use App\Repository\WltModule\AgreementActivityRealizationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AgreementActivityRealizationRepository::class)]
#[ORM\Table(name: 'wlt_agreement_activity_realization')]
class AgreementActivityRealization
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Agreement::class, inversedBy: 'evaluatedActivityRealizations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agreement $agreement = null;

    #[ORM\ManyToOne(targetEntity: ActivityRealization::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ActivityRealization $activityRealization = null;

    #[ORM\ManyToOne(targetEntity: ActivityRealizationGrade::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ActivityRealizationGrade $grade = null;

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Person $gradedBy = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $gradedOn = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $disabled = false;

    /**
     * @var Collection<int, AgreementActivityRealizationComment>
     */
    #[ORM\OneToMany(targetEntity: AgreementActivityRealizationComment::class, mappedBy: 'agreementActivityRealization')]
    #[ORM\OrderBy(['timestamp' => Criteria::ASC])]
    private Collection $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAgreement(): ?Agreement
    {
        return $this->agreement;
    }

    public function setAgreement(Agreement $agreement): static
    {
        $this->agreement = $agreement;
        return $this;
    }

    public function getActivityRealization(): ?ActivityRealization
    {
        return $this->activityRealization;
    }

    public function setActivityRealization(ActivityRealization $activityRealization): static
    {
        $this->activityRealization = $activityRealization;
        return $this;
    }

    public function getGrade(): ?ActivityRealizationGrade
    {
        return $this->grade;
    }

    public function setGrade(?ActivityRealizationGrade $grade): static
    {
        $this->grade = $grade;
        return $this;
    }

    public function getGradedBy(): ?Person
    {
        return $this->gradedBy;
    }

    public function setGradedBy(?Person $gradedBy): static
    {
        $this->gradedBy = $gradedBy;
        return $this;
    }

    public function getGradedOn(): ?\DateTimeInterface
    {
        return $this->gradedOn;
    }

    public function setGradedOn(?\DateTimeInterface $gradedOn): static
    {
        $this->gradedOn = $gradedOn;
        return $this;
    }

    public function isDisabled(): ?bool
    {
        return $this->disabled;
    }

    public function setDisabled(bool $disabled): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @return Collection<int, AgreementActivityRealizationComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }
}
