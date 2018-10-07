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

/**
 * @ORM\Entity
 * @ORM\Table(name="ict_ticket")
 */
class Ticket
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
     * @ORM\JoinColumn(nullable=false)
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\ICT\Element")
     * @ORM\JoinColumn(nullable=false)
     * @var Element
     */
    private $element;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(nullable=false)
     * @var Person
     */
    private $createdBy;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(nullable=true)
     * @var Person
     */
    private $assignee;
    
    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(nullable=true)
     * @var Person
     */
    private $closedBy;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $createdOn;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $lastUpdatedOn;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @var \DateTime
     */
    private $closedOn;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $dueOn;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $priority;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $notes;

    /**
     * @ORM\ManyToOne(targetEntity="Ticket")
     * @var Ticket
     */
    private $duplicates;

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
     * @return Ticket
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
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
     * @return Ticket
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Element
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * @param Element $element
     * @return Ticket
     */
    public function setElement($element)
    {
        $this->element = $element;
        return $this;
    }

    /**
     * @return Person
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * @param Person $createdBy
     * @return Ticket
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    /**
     * @return Person
     */
    public function getAssignee()
    {
        return $this->assignee;
    }

    /**
     * @param Person $assignee
     * @return Ticket
     */
    public function setAssignee($assignee)
    {
        $this->assignee = $assignee;
        return $this;
    }

    /**
     * @return Person
     */
    public function getClosedBy()
    {
        return $this->closedBy;
    }

    /**
     * @param Person $closedBy
     * @return Ticket
     */
    public function setClosedBy($closedBy)
    {
        $this->closedBy = $closedBy;
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
     * @return Ticket
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastUpdatedOn()
    {
        return $this->lastUpdatedOn;
    }

    /**
     * @param \DateTime $lastUpdatedOn
     * @return Ticket
     */
    public function setLastUpdatedOn($lastUpdatedOn)
    {
        $this->lastUpdatedOn = $lastUpdatedOn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getClosedOn()
    {
        return $this->closedOn;
    }

    /**
     * @param \DateTime $closedOn
     * @return Ticket
     */
    public function setClosedOn($closedOn)
    {
        $this->closedOn = $closedOn;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDueOn()
    {
        return $this->dueOn;
    }

    /**
     * @param \DateTime $dueOn
     * @return Ticket
     */
    public function setDueOn($dueOn)
    {
        $this->dueOn = $dueOn;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return Ticket
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     * @return Ticket
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return Ticket
     */
    public function getDuplicates()
    {
        return $this->duplicates;
    }

    /**
     * @param Ticket $duplicates
     * @return Ticket
     */
    public function setDuplicates($duplicates)
    {
        $this->duplicates = $duplicates;
        return $this;
    }
}
