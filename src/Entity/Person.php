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

use App\Repository\PersonRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: PersonRepository::class)]
#[UniqueEntity('loginUsername')]
#[UniqueEntity('emailAddress')]
class Person implements UserInterface, PasswordAuthenticatedUserInterface, \Stringable
{
    public const GENDER_NEUTRAL = 0;
    public const GENDER_MALE = 1;
    public const GENDER_FEMALE = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    private ?string $firstName = null;

    #[ORM\Column(type: Types::STRING)]
    #[Assert\NotBlank]
    private ?string $lastName = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $internalCode = null;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    private ?string $uniqueIdentifier = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $gender = self::GENDER_NEUTRAL;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Assert\Regex(pattern: '/[@ ]{1,}/', message: 'login_username.invalid_chars', htmlPattern: false, match: false)]
    private ?string $loginUsername = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false)]
    private bool $forcePasswordChange = false;
    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $enabled = true;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $globalAdministrator = false;

    #[ORM\Column(type: Types::STRING, unique: true, nullable: true)]
    #[Assert\Email]
    private ?string $emailAddress = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $token = null;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $tokenType = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $tokenExpiration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastAccess = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $blockedUntil = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(nullable: true)]
    protected ?Organization $defaultOrganization = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $externalCheck = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    protected ?bool $allowExternalCheck = false;

    public function __toString(): string
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * Convertir usuario en cadena extendida
     */
    public function getFullDisplayName(): string
    {
        $prefix = $this->getFirstName() . ' ' . $this->getLastName();
        return $this->getUniqueIdentifier() !== '' && $this->getUniqueIdentifier() !== null
            ? $prefix . ' - ' . $this->getUniqueIdentifier() : $prefix;
    }

    /**
     * Convertir usuario en cadena con nombre de usuario
     */
    public function getFullName(): string
    {
        return $this.' ('.$this->getUsernameAndEmailAddress().')';
    }

    public function getUsernameAndEmailAddress(): string
    {
        return $this->loginUsername.(($this->loginUsername && $this->emailAddress) ? ' - ' : '').$this->emailAddress;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getInternalCode(): ?string
    {
        return $this->internalCode;
    }

    public function setInternalCode(?string $internalCode): static
    {
        $this->internalCode = $internalCode;
        return $this;
    }

    public function getUniqueIdentifier(): ?string
    {
        return $this->uniqueIdentifier;
    }

    public function setUniqueIdentifier(?string $uniqueIdentifier): static
    {
        $this->uniqueIdentifier = $uniqueIdentifier;
        return $this;
    }

    public function getGender(): ?int
    {
        return $this->gender;
    }

    public function setGender(int $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getLoginUsername(): ?string
    {
        return $this->loginUsername;
    }

    public function setLoginUsername(?string $loginUsername): static
    {
        $this->loginUsername = $loginUsername;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function isForcePasswordChange(): bool
    {
        return $this->forcePasswordChange;
    }

    public function setForcePasswordChange(bool $forcePasswordChange): static
    {
        $this->forcePasswordChange = $forcePasswordChange;
        return $this;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): static
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isGlobalAdministrator(): ?bool
    {
        return $this->globalAdministrator;
    }

    public function setGlobalAdministrator(bool $globalAdministrator): static
    {
        $this->globalAdministrator = $globalAdministrator;

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

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getTokenType(): ?string
    {
        return $this->tokenType;
    }

    public function setTokenType(?string $tokenType): static
    {
        $this->tokenType = $tokenType;

        return $this;
    }

    public function getTokenExpiration(): ?\DateTimeInterface
    {
        return $this->tokenExpiration;
    }

    public function setTokenExpiration(?\DateTimeInterface $tokenExpiration): static
    {
        $this->tokenExpiration = $tokenExpiration;

        return $this;
    }

    public function getLastAccess(): ?\DateTimeInterface
    {
        return $this->lastAccess;
    }

    public function setLastAccess(\DateTimeInterface $lastAccess): static
    {
        $this->lastAccess = $lastAccess;

        return $this;
    }

    public function getBlockedUntil(): ?\DateTimeInterface
    {
        return $this->blockedUntil;
    }

    public function setBlockedUntil(\DateTimeInterface $blockedUntil): static
    {
        $this->blockedUntil = $blockedUntil;

        return $this;
    }

    public function getDefaultOrganization(): ?Organization
    {
        return $this->defaultOrganization;
    }

    public function setDefaultOrganization(Organization $defaultOrganization): static
    {
        $this->defaultOrganization = $defaultOrganization;

        return $this;
    }



    public function getExternalCheck(): ?bool
    {
        return $this->externalCheck;
    }

    public function setExternalCheck(bool $externalCheck): static
    {
        $this->externalCheck = $this->allowExternalCheck && $externalCheck;

        return $this;
    }

    public function getAllowExternalCheck(): ?bool
    {
        return $this->allowExternalCheck;
    }

    public function setAllowExternalCheck(bool $allowExternalCheck): static
    {
        $this->allowExternalCheck = $allowExternalCheck;

        if (!$allowExternalCheck) {
            $this->externalCheck = false;
        }

        return $this;
    }
    public function getUsername(): ?string
    {
        return $this->getLoginUsername() ?: $this->getEmailAddress();
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->isGlobalAdministrator()) {
            $roles[] = 'ROLE_ADMIN';
        }
        return $roles;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
    }

    #[Assert\Callback]
    public function validate(ExecutionContextInterface $context): void
    {
        // comprobar si se ha especificado al menos el nombre de usuario o el correo electrónico
        if (!$this->getLoginUsername() && !$this->getEmailAddress()) {
            $context->buildViolation('user.id.not_found')
                ->atPath('loginUsername')
                ->addViolation();
            $context->buildViolation('user.id.not_found')
                ->atPath('emailAddress')
                ->addViolation();
        }
    }
}
