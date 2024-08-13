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

use App\Repository\CompanyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CompanyRepository::class)]
#[ORM\Table(name: 'company')]
class Company implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

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

    #[ORM\ManyToOne(targetEntity: Person::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Person $manager = null;

    #[ORM\OneToMany(targetEntity: Workcenter::class, mappedBy: 'company')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $workcenters;

    public function __construct()
    {
        $this->workcenters = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getName();
    }

    public function getFullName(): string
    {
        return $this->getCode() . ' - ' . $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): void
    {
        $this->code = $code;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(?string $zipCode): void
    {
        $this->zipCode = $zipCode;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): void
    {
        $this->phoneNumber = $phoneNumber;
    }

    public function getFaxNumber(): ?string
    {
        return $this->faxNumber;
    }

    public function setFaxNumber(?string $faxNumber): void
    {
        $this->faxNumber = $faxNumber;
    }

    public function getEmailAddress(): ?string
    {
        return $this->emailAddress;
    }

    public function setEmailAddress(?string $emailAddress): void
    {
        $this->emailAddress = $emailAddress;
    }

    public function getWebSite(): ?string
    {
        return $this->webSite;
    }

    public function setWebSite(?string $webSite): void
    {
        $this->webSite = $webSite;
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

    /**
     * @return Collection<int, Workcenter>
     */
    public function getWorkcenters(): Collection
    {
        return $this->workcenters;
    }
}
