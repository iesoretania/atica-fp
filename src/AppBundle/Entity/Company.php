<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CompanyRepository")
 * @ORM\Table(name="company")
 */
class Company
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $address;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $city;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $zipCode;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $phoneNumber;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $faxNumber;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Assert\Email
     * @var string
     */
    private $emailAddress;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $webSite;

    /**
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(nullable=true)
     * @var Person
     */
    private $manager;

    /**
     * @ORM\OneToMany(targetEntity="Workcenter", mappedBy="company")
     * @ORM\OrderBy({"name": "ASC"})
     * @var Workcenter[]
     */
    private $workcenters;

    public function __construct()
    {
        $this->workcenters = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function getFullName()
    {
        return $this->getCode() . ' - ' . $this->getName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
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
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
    }

    /**
     * @return string
     */
    public function getPhoneNumber()
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * @return string
     */
    public function getFaxNumber()
    {
        return $this->faxNumber;
    }

    /**
     * @param string $faxNumber
     */
    public function setFaxNumber($faxNumber)
    {
        $this->faxNumber = $faxNumber;
    }

    /**
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * @param string $emailAddress
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
    }

    /**
     * @return string
     */
    public function getWebSite()
    {
        return $this->webSite;
    }

    /**
     * @param string $webSite
     */
    public function setWebSite($webSite)
    {
        $this->webSite = $webSite;
    }

    /**
     * @return Person|null
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Person|null $manager
     * @return Company
     */
    public function setManager(Person $manager = null)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return Workcenter[]
     */
    public function getWorkcenters()
    {
        return $this->workcenters;
    }
}
