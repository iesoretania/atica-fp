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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ict_work_order")
 */
class WorkOrder
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
     * @ORM\ManyToOne(targetEntity="Ticket")
     * @ORM\JoinColumn(nullable=true)
     * @var Ticket
     */
    private $ticket;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @var string
     */
    private $description;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @var \DateTime
     */
    private $startedOn;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $finishedOn;

    /**
     * @ORM\OneToMany(targetEntity="UsedConsumable", mappedBy="workOrder")
     * @var UsedConsumable[]
     */
    private $usedConsumables;

    public function __construct()
    {
        $this->usedConsumables = new ArrayCollection();
    }

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
     * @return WorkOrder
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
     * @return WorkOrder
     */
    public function setPerson($person)
    {
        $this->person = $person;
        return $this;
    }

    /**
     * @return Ticket
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * @param Ticket $ticket
     * @return WorkOrder
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;
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
     * @return WorkOrder
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartedOn()
    {
        return $this->startedOn;
    }

    /**
     * @param \DateTime $startedOn
     * @return WorkOrder
     */
    public function setStartedOn($startedOn)
    {
        $this->startedOn = $startedOn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getFinishedOn()
    {
        return $this->finishedOn;
    }

    /**
     * @param \DateTime $finishedOn
     * @return WorkOrder
     */
    public function setFinishedOn($finishedOn)
    {
        $this->finishedOn = $finishedOn;
        return $this;
    }

    /**
     * @return UsedConsumable[]
     */
    public function getUsedConsumables()
    {
        return $this->usedConsumables;
    }

    /**
     * @param UsedConsumable[] $usedConsumables
     * @return WorkOrder
     */
    public function setUsedConsumables($usedConsumables)
    {
        $this->usedConsumables = $usedConsumables;
        return $this;
    }
}
