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

use App\Entity\Edu\AcademicYear;
use App\Repository\OrganizationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrganizationRepository::class)]
#[UniqueEntity('code')]
class Organization implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = 0;

    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    #[ORM\Column(type: Types::STRING, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::STRING)]
    private ?string $city = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $zipCode = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $faxNumber = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Assert\Email]
    private ?string $emailAddress = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $webSite = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\OneToOne(targetEntity: AcademicYear::class)]
    private ?AcademicYear $currentAcademicYear = null;

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setFaxNumber(?string $faxNumber): static
    {
        $this->faxNumber = $faxNumber;

        return $this;
    }

    public function getFaxNumber(): ?string
    {
        return $this->faxNumber;
    }

    public function setEmailAddress(?string $emailAddress): static
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setWebSite(?string $webSite): static
    {
        $this->webSite = $webSite;

        return $this;
    }

    public function getWebSite(): ?string
    {
        return $this->webSite;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCurrentAcademicYear(): ?AcademicYear
    {
        return $this->currentAcademicYear;
    }

    public function setCurrentAcademicYear(AcademicYear $currentAcademicYear): static
    {
        $this->currentAcademicYear = $currentAcademicYear;
        return $this;
    }
}
