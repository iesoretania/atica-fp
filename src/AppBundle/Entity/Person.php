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

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\PersonRepository")
 */
class Person
{
    const GENDER_NEUTRAL = 0;
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @var string
     */
    private $firstName;

    /**
     * @ORM\Column(type="string")
     * @Assert\NotBlank
     * @var string
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $internalCode;

    /**
     * @ORM\Column(type="string", nullable=true, unique=true)
     * @var string
     */
    private $uniqueIdentifier;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    private $gender;

    /**
     * @ORM\OneToOne(targetEntity="User", mappedBy="person")
     * @var User|null
     */
    private $user;

    /**
     * Convertir usuario en cadena
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * Convertir usuario en cadena extendida
     *
     * @return string
     */
    public function getFullDisplayName()
    {
        $prefix = $this->getFirstName() . ' ' . $this->getLastName();
        return $this->getUniqueIdentifier() ? $prefix . ' - ' . $this->getUniqueIdentifier() : $prefix;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->gender = self::GENDER_NEUTRAL;
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
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return Person
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return Person
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return string
     */
    public function getInternalCode()
    {
        return $this->internalCode;
    }

    /**
     * @param string $internalCode
     * @return Person
     */
    public function setInternalCode($internalCode)
    {
        $this->internalCode = $internalCode;
        return $this;
    }

    /**
     * @return string
     */
    public function getUniqueIdentifier()
    {
        return $this->uniqueIdentifier;
    }

    /**
     * @param string $uniqueIdentifier
     * @return Person
     */
    public function setUniqueIdentifier($uniqueIdentifier)
    {
        $this->uniqueIdentifier = $uniqueIdentifier;
        return $this;
    }

    /**
     * @return int
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * @param int $gender
     * @return Person
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
