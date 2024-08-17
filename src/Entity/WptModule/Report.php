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

use App\Repository\WptModule\ReportRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReportRepository::class)]
#[ORM\Table(name: 'wpt_report')]
class Report
{
    public const GRADE_NEGATIVE = 0;
    public const GRADE_POSITIVE = 1;
    public const GRADE_EXCELLENT = 2;

    #[ORM\Id]
    #[ORM\OneToOne(targetEntity: AgreementEnrollment::class, inversedBy: 'report')]
    private ?AgreementEnrollment $agreementEnrollment = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $workActivities = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $professionalCompetence = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $organizationalCompetence = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $relationalCompetence = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $contingencyResponse = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $otherDescription1 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $other1 = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $otherDescription2 = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $other2 = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $proposedChanges = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $signDate = null;

    public function getAgreementEnrollment(): ?AgreementEnrollment
    {
        return $this->agreementEnrollment;
    }

    public function setAgreementEnrollment(AgreementEnrollment $agreementEnrollment): static
    {
        $this->agreementEnrollment = $agreementEnrollment;
        return $this;
    }

    public function getWorkActivities(): ?string
    {
        return $this->workActivities;
    }

    public function setWorkActivities(?string $workActivities): static
    {
        $this->workActivities = $workActivities;
        return $this;
    }

    public function getProfessionalCompetence(): ?int
    {
        return $this->professionalCompetence;
    }

    public function setProfessionalCompetence(?int $professionalCompetence): static
    {
        $this->professionalCompetence = $professionalCompetence;
        return $this;
    }

    public function getOrganizationalCompetence(): ?int
    {
        return $this->organizationalCompetence;
    }

    public function setOrganizationalCompetence(?int $organizationalCompetence): static
    {
        $this->organizationalCompetence = $organizationalCompetence;
        return $this;
    }

    public function getRelationalCompetence(): ?int
    {
        return $this->relationalCompetence;
    }

    public function setRelationalCompetence(?int $relationalCompetence): static
    {
        $this->relationalCompetence = $relationalCompetence;
        return $this;
    }

    public function getContingencyResponse(): ?int
    {
        return $this->contingencyResponse;
    }

    public function setContingencyResponse(?int $contingencyResponse): static
    {
        $this->contingencyResponse = $contingencyResponse;
        return $this;
    }

    public function getOtherDescription1(): ?string
    {
        return $this->otherDescription1;
    }

    public function setOtherDescription1(?string $otherDescription1): static
    {
        $this->otherDescription1 = $otherDescription1;
        return $this;
    }

    public function getOther1(): ?int
    {
        return $this->other1;
    }

    public function setOther1(?int $other1): static
    {
        $this->other1 = $other1;
        return $this;
    }

    public function getOtherDescription2(): ?string
    {
        return $this->otherDescription2;
    }

    public function setOtherDescription2(?string $otherDescription2): static
    {
        $this->otherDescription2 = $otherDescription2;
        return $this;
    }

    public function getOther2(): ?int
    {
        return $this->other2;
    }

    public function setOther2(?int $other2): static
    {
        $this->other2 = $other2;
        return $this;
    }

    public function getProposedChanges(): ?string
    {
        return $this->proposedChanges;
    }

    public function setProposedChanges(?string $proposedChanges): static
    {
        $this->proposedChanges = $proposedChanges;
        return $this;
    }

    public function getSignDate(): ?\DateTimeInterface
    {
        return $this->signDate;
    }

    public function setSignDate(\DateTimeInterface $signDate): static
    {
        $this->signDate = $signDate;
        return $this;
    }
}
