<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

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

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WorkcenterRepository")
 * @ORM\Table(name="workcenter")
 */
class Workcenter
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Company", inversedBy="workcenters")
     * @ORM\JoinColumn(nullable=false)
     * @var Company
     */
    private $company;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $name;

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
     * @ORM\ManyToOne(targetEntity="Person")
     * @ORM\JoinColumn(nullable=true)
     * @var Person
     */
    private $manager;

    public function __toString()
    {
        return $this->getCompany()->getName() . ' - ' . $this->getName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param Company $company
     * @return Workcenter
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
        return $this;
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
     * @return Workcenter
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @return Workcenter
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
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
     * @return Workcenter
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
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
     * @return Workcenter
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;
        return $this;
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
     * @return Workcenter
     */
    public function setPhoneNumber($phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
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
     * @return Workcenter
     */
    public function setFaxNumber($faxNumber)
    {
        $this->faxNumber = $faxNumber;
        return $this;
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
     * @return Workcenter
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;
        return $this;
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
     * @return Workcenter
     */
    public function setManager(Person $manager = null)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @param Company $company
     * @return Workcenter
     */
    public function initFromCompany(Company $company)
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
