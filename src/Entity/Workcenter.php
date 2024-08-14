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

use App\Repository\WorkcenterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: WorkcenterRepository::class)]
#[ORM\Table(name: 'workcenter')]
class Workcenter implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'workcenters')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(type: Types::STRING, nullable: false)]
    private string $name;

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

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Person $manager = null;

    public function __toString(): string
    {
        return ($this->getCompany() instanceof Company)
            ? $this->getCompany()->getName() . ' - ' . $this->getName()
            : '';
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): static
    {
        $this->zipCode = $zipCode;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getFaxNumber(): ?string
    {
        return $this->faxNumber;
    }

    public function setFaxNumber(?string $faxNumber): static
    {
        $this->faxNumber = $faxNumber;
        return $this;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): static
    {
        $this->emailAddress = $emailAddress;
        return $this;
    }

    public function getManager(): ?Person
    {
        return $this->manager;
    }

    public function setManager(Person $manager = null): static
    {
        $this->manager = $manager;
        return $this;
    }

    public function initFromCompany(Company $company): static
    {
        $this
            ->setCompany($company)
            ->setAddress($company->getAddress())
            ->setCity($company->getCity())
            ->setZipCode($company->getZipCode())
            ->setPhoneNumber($company->getPhoneNumber())
            ->setFaxNumber($company->getFaxNumber())
            ->setEmailAddress($company->getEmailAddress())
            ->setManager($company->getManager());

        return $this;
    }
}
