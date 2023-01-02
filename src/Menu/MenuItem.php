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

namespace App\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class MenuItem
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $caption;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @var array
     */
    protected $routeParams = [];

    /**
     * @var string
     */
    protected $icon;

    /**
     * @var Collection
     */
    protected $children;

    /**
     * @var MenuItem|null
     */
    protected $parent;

    /**
     * @var integer
     */
    protected $priority;

    /**
     * MenuItem constructor
     */
    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->parent = null;
        $this->priority = 0;
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
     * @return MenuItem
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @param string $caption
     * @return MenuItem
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;
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
     * @return MenuItem
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getRouteName()
    {
        return $this->routeName;
    }

    /**
     * @param string $routeName
     * @return MenuItem
     */
    public function setRouteName($routeName)
    {
        $this->routeName = $routeName;
        return $this;
    }

    /**
     * @return array
     */
    public function getRouteParams()
    {
        return $this->routeParams;
    }

    /**
     * @param array $routeParams
     * @return MenuItem
     */
    public function setRouteParams($routeParams)
    {
        $this->routeParams = $routeParams;
        return $this;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param string $icon
     * @return MenuItem
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param MenuItem $child
     * @return MenuItem
     */
    public function addChild(MenuItem $child)
    {
        $this->children->add($child);

        $iterator = $this->children->getIterator();
        $iterator->uasort(function (MenuItem $a, MenuItem $b) {
            if ($a->getPriority() === $b->getPriority()) {
                return $a->getName() < $b->getName() ? -1 : 1;
            }
            return ($a->getPriority() < $b->getPriority()) ? -1 : 1;
        });
        $this->children = new ArrayCollection(iterator_to_array($iterator));

        $child->setParent($this);
        return $this;
    }

    /**
     * @param MenuItem $child
     * @return MenuItem
     */
    public function removeChild(MenuItem $child)
    {
        $index = $this->children->indexOf($child);
        if (false !== $index) {
            $this->children->remove($index);
        }
        return $this;
    }

    /**
     * @return MenuItem|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param MenuItem|null $parent
     * @return MenuItem
     */
    public function setParent(MenuItem $parent)
    {
        $this->parent = $parent;
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
     * @return MenuItem
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return MenuItem[]
     */
    public function getPath()
    {
        $path = [];
        $current = $this;

        while (null !== $current) {
            array_unshift($path, $current);
            $current = $current->getParent();
        }

        return $path;
    }
}
