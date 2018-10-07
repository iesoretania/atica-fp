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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ict_used_consumable")
 */
class UsedConsumable
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="WorkOrder", inversedBy="usedConsumables")
     * @ORM\JoinColumn(nullable=false)
     */
    private $workOrder;

    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Consumable")
     * @ORM\JoinColumn(nullable=false)
     */
    private $consumable;

    /**
     * @ORM\Column(type="integer")
     * @var integer
     */
    private $quantity;

    /**
     * @return mixed
     */
    public function getWorkOrder()
    {
        return $this->workOrder;
    }

    /**
     * @param mixed $workOrder
     * @return UsedConsumable
     */
    public function setWorkOrder($workOrder)
    {
        $this->workOrder = $workOrder;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getConsumable()
    {
        return $this->consumable;
    }

    /**
     * @param mixed $consumable
     * @return UsedConsumable
     */
    public function setConsumable($consumable)
    {
        $this->consumable = $consumable;
        return $this;
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @param int $quantity
     * @return UsedConsumable
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
        return $this;
    }
}
