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

namespace AppBundle\Entity\WPT;

use AppBundle\Entity\Edu\Criterion;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WPT\ActivityRepository")
 * @ORM\Table(name="wpt_activity",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"shift_id", "code"})}))))
 */
class Activity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Shift", inversedBy="activities")
     * @ORM\JoinColumn(nullable=false)
     * @var Shift
     */
    private $shift;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    private $description;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Edu\Criterion")
     * @ORM\JoinTable(name="wpt_activity_criterion")
     * @var Criterion[]|Collection
     */
    protected $criteria;

    public function __construct()
    {
        $this->criteria = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getCode() . ': ' . $this->getDescription();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Shift
     */
    public function getShift()
    {
        return $this->shift;
    }

    /**
     * @param Shift $shift
     * @return Activity
     */
    public function setShift($shift)
    {
        $this->shift = $shift;
        return $this;
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
     * @return Activity
     */
    public function setCode($code)
    {
        $this->code = $code;
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
     * @return Activity
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Criterion[]|Collection
     */
    public function getCriteria()
    {
        return $this->criteria;
    }

    /**
     * @param Criterion[]|Collection $criteria
     * @return Activity
     */
    public function setCriteria($criteria)
    {
        $this->criteria = $criteria;
        return $this;
    }
}
