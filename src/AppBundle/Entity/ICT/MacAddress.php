<?php
/*
  Copyright (C) 2018: Luis Ramón López López

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


namespace AppBundle\Entity\ICT;

use AppBundle\Entity\Organization;
use AppBundle\Entity\Person;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ICT\MacAddressRepository")
 * @ORM\Table(name="ict_mac_address")
 */
class MacAddress
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
     * @ORM\JoinColumn(nullable=false)
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(nullable=false)
     * @var Person
     */
    private $person;

    /**
     * @ORM\Column(type="string", nullable=false, length=17)
     * @Assert\Regex(pattern="/^([a-fA-F0-9]{2}:){5}[a-fA-F0-9]{2}$/", message="mac_address.invalid")
     * @var string
     */
    private $address;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="date", nullable=false)
     * @var \DateTime
     */
    private $createdOn;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $registeredOn;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $unRegisteredOn;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     * @return MacAddress
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param Person $person
     * @return MacAddress
     */
    public function setPerson($person)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return MacAddress
     */
    public function setAddress($address)
    {
        $this->address = strtoupper($address);
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return MacAddress
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * @param \DateTime $createdOn
     * @return MacAddress
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRegisteredOn()
    {
        return $this->registeredOn;
    }

    /**
     * @param \DateTime $registeredOn
     * @return MacAddress
     */
    public function setRegisteredOn($registeredOn)
    {
        $this->registeredOn = $registeredOn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUnRegisteredOn()
    {
        return $this->unRegisteredOn;
    }

    /**
     * @param \DateTime $unRegisteredOn
     * @return MacAddress
     */
    public function setUnRegisteredOn($unRegisteredOn)
    {
        $this->unRegisteredOn = $unRegisteredOn;
        return $this;
    }
}
