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

namespace App\Entity\WPT;

use App\Entity\Edu\Teacher;
use App\Entity\Edu\TravelRoute;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="wpt_travel_expense")
 */
class TravelExpense
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $fromDateTime;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTime
     */
    private $toDateTime;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\Teacher")
     * @ORM\JoinColumn(nullable=false)
     * @var Teacher
     */
    private $teacher;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\TravelRoute")
     * @ORM\JoinColumn(nullable=true)
     * @var TravelRoute
     */
    private $travelRoute;

    /**
     * @ORM\ManyToMany(targetEntity="Agreement")
     * @ORM\JoinTable(name="wpt_travel_expense_agreement")
     * @var Agreement[]|Collection
     */
    private $agreements;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    private $otherExpensesDescription;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    private $otherExpenses;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    private $description;

    public function __construct()
    {
        $this->agreements = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \DateTime
     */
    public function getFromDateTime()
    {
        return $this->fromDateTime;
    }

    /**
     * @param \DateTime $fromDateTime
     * @return TravelExpense
     */
    public function setFromDateTime(\DateTimeInterface $fromDateTime)
    {
        $this->fromDateTime = $fromDateTime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getToDateTime()
    {
        return $this->toDateTime;
    }

    /**
     * @param \DateTime $toDateTime
     * @return TravelExpense
     */
    public function setToDateTime(\DateTimeInterface $toDateTime)
    {
        $this->toDateTime = $toDateTime;
        return $this;
    }

    /**
     * @return Teacher
     */
    public function getTeacher()
    {
        return $this->teacher;
    }

    /**
     * @param Teacher $teacher
     * @return TravelExpense
     */
    public function setTeacher($teacher)
    {
        $this->teacher = $teacher;
        return $this;
    }

    /**
     * @return TravelRoute
     */
    public function getTravelRoute()
    {
        return $this->travelRoute;
    }

    /**
     * @param TravelRoute $travelRoute
     * @return TravelExpense
     */
    public function setTravelRoute($travelRoute)
    {
        $this->travelRoute = $travelRoute;
        return $this;
    }

    /**
     * @return Agreement[]|Collection
     */
    public function getAgreements()
    {
        return $this->agreements;
    }

    /**
     * @param Agreement[]|Collection $agreements
     * @return TravelExpense
     */
    public function setAgreements($agreements)
    {
        $this->agreements = $agreements;
        return $this;
    }

    /**
     * @return string
     */
    public function getOtherExpensesDescription()
    {
        return $this->otherExpensesDescription;
    }

    /**
     * @param string $otherExpensesDescription
     * @return TravelExpense
     */
    public function setOtherExpensesDescription($otherExpensesDescription)
    {
        $this->otherExpensesDescription = $otherExpensesDescription;
        return $this;
    }

    /**
     * @return int
     */
    public function getOtherExpenses()
    {
        return $this->otherExpenses;
    }

    /**
     * @param int $otherExpenses
     * @return TravelExpense
     */
    public function setOtherExpenses($otherExpenses)
    {
        $this->otherExpenses = $otherExpenses;
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
     * @return TravelExpense
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
}
