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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\MembershipRepository")
 */
class Membership
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="memberships")
     * @ORM\JoinColumn(nullable=false)
     * @var User
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="memberships")
     * @ORM\JoinColumn(nullable=false)
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    private $validFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $validUntil;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set validFrom
     *
     * @param \DateTime $validFrom
     *
     * @return Membership
     */
    public function setValidFrom(\DateTime $validFrom)
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    /**
     * Get validFrom
     *
     * @return \DateTime
     */
    public function getValidFrom()
    {
        return $this->validFrom;
    }

    /**
     * Set validUntil
     *
     * @param \DateTime|null $validUntil
     *
     * @return Membership
     */
    public function setValidUntil(\DateTime $validUntil = null)
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    /**
     * Get validUntil
     *
     * @return \DateTime
     */
    public function getValidUntil()
    {
        return $this->validUntil;
    }

    /**
     * Set user
     *
     * @param User $user
     *
     * @return Membership
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set organization
     *
     * @param Organization $organization
     *
     * @return Membership
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * Get organization
     *
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }
}
