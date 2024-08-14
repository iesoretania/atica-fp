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

use App\Entity\Organization;
use App\Repository\Edu\AcademicYearRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AcademicYearRepository::class)]
#[ORM\Table(name: 'edu_academic_year')]
class AcademicYear implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organization $organization = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Teacher $principal = null;

    #[ORM\ManyToOne(targetEntity: Teacher::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Teacher $financialManager = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $defaultPortraitTemplate = null;

    #[ORM\ManyToOne(targetEntity: ReportTemplate::class)]
    private ?ReportTemplate $defaultLandscapeTemplate = null;

    public function __toString(): string
    {
        return (string) $this->getDescription();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrincipal(): ?Teacher
    {
        return $this->principal;
    }

    public function setPrincipal(?Teacher $principal): static
    {
        $this->principal = $principal;
        return $this;
    }

    public function getFinancialManager(): ?Teacher
    {
        return $this->financialManager;
    }

    public function setFinancialManager(?Teacher $financialManager): static
    {
        $this->financialManager = $financialManager;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getDefaultPortraitTemplate(): ?ReportTemplate
    {
        return $this->defaultPortraitTemplate;
    }

    public function setDefaultPortraitTemplate(?ReportTemplate $defaultPortraitTemplate): static
    {
        $this->defaultPortraitTemplate = $defaultPortraitTemplate;
        return $this;
    }

    public function getDefaultLandscapeTemplate(): ?ReportTemplate
    {
        return $this->defaultLandscapeTemplate;
    }

    public function setDefaultLandscapeTemplate(?ReportTemplate $defaultLandscapeTemplate): static
    {
        $this->defaultLandscapeTemplate = $defaultLandscapeTemplate;
        return $this;
    }
}
