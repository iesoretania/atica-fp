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

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PersonRepository")
 * @UniqueEntity("loginUsername")
 * @UniqueEntity("emailAddress")
 */
class Person implements UserInterface
{
    public const GENDER_NEUTRAL = 0;
    public const GENDER_MALE = 1;
    public const GENDER_FEMALE = 2;

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
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Regex(pattern="/[@ ]{1,}/", match=false, message="login_username.invalid_chars", htmlPattern=false)
     * @var string
     */
    private $loginUsername;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $password;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     * @var bool
     */
    private $forcePasswordChange;
    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $enabled;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $globalAdministrator;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     * @Assert\Email
     * @var string
     */
    private $emailAddress;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $token;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $tokenType;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $tokenExpiration;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $lastAccess;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $blockedUntil;

    /**
     * @ORM\ManyToOne(targetEntity="Organization")
     * @ORM\JoinColumn(nullable=true)
     * @var Organization|null
     */
    protected $defaultOrganization;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $externalCheck;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $allowExternalCheck;

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
        return $this->getUniqueIdentifier() !== '' && $this->getUniqueIdentifier() !== null ? $prefix . ' - ' . $this->getUniqueIdentifier() : $prefix;
    }

    /**
     * Convertir usuario en cadena con nombre de usuario
     *
     * @return string
     */
    public function getFullName()
    {
        return $this.' ('.$this->getUsernameAndEmailAddress().')';
    }

    /**
     * @return string
     */
    public function getUsernameAndEmailAddress()
    {
        return $this->loginUsername.(($this->loginUsername && $this->emailAddress) ? ' - ' : '').$this->emailAddress;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->gender = self::GENDER_NEUTRAL;

        $this->externalCheck = false;
        $this->allowExternalCheck = false;
        $this->enabled = true;
        $this->globalAdministrator = false;
        $this->forcePasswordChange = false;
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
     * Set userName
     *
     * @param string $loginUsername
     *
     * @return Person
     */
    public function setLoginUsername($loginUsername)
    {
        $this->loginUsername = $loginUsername;

        return $this;
    }

    /**
     * Get userName
     *
     * @return string
     */
    public function getLoginUsername()
    {
        return $this->loginUsername;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Person
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return bool
     */
    public function isForcePasswordChange()
    {
        return $this->forcePasswordChange;
    }

    /**
     * @param bool $forcePasswordChange
     * @return Person
     */
    public function setForcePasswordChange($forcePasswordChange)
    {
        $this->forcePasswordChange = $forcePasswordChange;
        return $this;
    }


    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return Person
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }


    /**
     * Set globalAdmin
     *
     * @param boolean $globalAdministrator
     *
     * @return Person
     */
    public function setGlobalAdministrator($globalAdministrator)
    {
        $this->globalAdministrator = $globalAdministrator;

        return $this;
    }

    /**
     * Get globalAdmin
     *
     * @return boolean
     */
    public function isGlobalAdministrator()
    {
        return $this->globalAdministrator;
    }

    /**
     * Set emailAddress
     *
     * @param string $emailAddress
     *
     * @return Person
     */
    public function setEmailAddress($emailAddress)
    {
        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Get emailAddress
     *
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->emailAddress;
    }

    /**
     * Set token
     *
     * @param string $token
     *
     * @return Person
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set tokenType
     *
     * @param string $tokenType
     *
     * @return Person
     */
    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;

        return $this;
    }

    /**
     * Get tokenType
     *
     * @return string
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * Set tokenExpiration
     *
     * @param ?\DateTime $tokenExpiration
     *
     * @return Person
     */
    public function setTokenExpiration(?\DateTimeInterface $tokenExpiration)
    {
        $this->tokenExpiration = $tokenExpiration;

        return $this;
    }

    /**
     * Get tokenExpiration
     *
     * @return \DateTime
     */
    public function getTokenExpiration()
    {
        return $this->tokenExpiration;
    }

    /**
     * Set lastAccess
     *
     * @param \DateTime $lastAccess
     *
     * @return Person
     */
    public function setLastAccess(\DateTimeInterface $lastAccess)
    {
        $this->lastAccess = $lastAccess;

        return $this;
    }

    /**
     * Get lastAccess
     *
     * @return \DateTime
     */
    public function getLastAccess()
    {
        return $this->lastAccess;
    }

    /**
     * Set blockedUntil
     *
     * @param \DateTime $blockedUntil
     *
     * @return Person
     */
    public function setBlockedUntil(\DateTimeInterface $blockedUntil)
    {
        $this->blockedUntil = $blockedUntil;

        return $this;
    }

    /**
     * Get blockedUntil
     *
     * @return \DateTime
     */
    public function getBlockedUntil()
    {
        return $this->blockedUntil;
    }

    /**
     * Set defaultOrganization
     *
     * @param Organization $defaultOrganization
     *
     * @return Person
     */
    public function setDefaultOrganization(Organization $defaultOrganization = null)
    {
        $this->defaultOrganization = $defaultOrganization;

        return $this;
    }

    /**
     * Get defaultOrganization
     *
     * @return Organization|null
     */
    public function getDefaultOrganization()
    {
        return $this->defaultOrganization;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        // comprobar si se ha especificado al menos el nombre de usuario o el correo electrónico
        if (!$this->getUniqueIdentifier() && !$this->getEmailAddress()) {
            $context->buildViolation('user.id.not_found')
                ->atPath('userName')
                ->addViolation();
            $context->buildViolation('user.id.not_found')
                ->atPath('emailAddress')
                ->addViolation();
        }
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->getLoginUsername() ?: $this->getEmailAddress();
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return string[] The user roles
     */
    public function getRoles()
    {
        $roles = ['ROLE_USER'];
        if ($this->isGlobalAdministrator()) {
            $roles[] = 'ROLE_ADMIN';
        }
        return $roles;
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * Removes sensitive data from the user.
     *
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
    }

    /**
     * Set externalCheck
     *
     * @param boolean $externalCheck
     *
     * @return Person
     */
    public function setExternalCheck($externalCheck)
    {
        $this->externalCheck = $this->allowExternalCheck && $externalCheck;

        return $this;
    }

    /**
     * Get externalCheck
     *
     * @return boolean
     */
    public function getExternalCheck()
    {
        return $this->externalCheck;
    }

    /**
     * Set allowExternalCheck
     *
     * @param boolean $allowExternalCheck
     *
     * @return Person
     */
    public function setAllowExternalCheck($allowExternalCheck)
    {
        $this->allowExternalCheck = $allowExternalCheck;

        if (!$allowExternalCheck) {
            $this->externalCheck = false;
        }

        return $this;
    }

    /**
     * Get allowExternalCheck
     *
     * @return boolean
     */
    public function getAllowExternalCheck()
    {
        return $this->allowExternalCheck;
    }
}
