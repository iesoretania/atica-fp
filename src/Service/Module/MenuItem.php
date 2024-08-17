<?php
/*
  Copyright (C) 2018-2024: Luis Ramón López López

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

namespace App\Service\Module;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class MenuItem
{
    private ?string $name;

    private ?string $caption;

    private ?string $description;

    private ?string $routeName;

    private array $routeParams = [];

    private ?string $icon;

    /**
     * @var Collection<int, MenuItem>
     */
    private Collection $children;

    private ?MenuItem $parent = null;

    private int $priority = 0;

    private ?string $module = null;

    public function __construct()
    {
        $this->children = new ArrayCollection();
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
     */
    public function setName($name): static
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
     */
    public function setCaption($caption): static
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
     */
    public function setDescription($description): static
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
     */
    public function setRouteName($routeName): static
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
     */
    public function setRouteParams($routeParams): static
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
     */
    public function setIcon($icon): static
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

    public function addChild(MenuItem $child): static
    {
        $this->children->add($child);

        $iterator = $this->children->getIterator();
        $iterator->uasort(function (MenuItem $a, MenuItem $b): int {
            if ($a->getPriority() === $b->getPriority()) {
                return $a->getName() < $b->getName() ? -1 : 1;
            }
            return ($a->getPriority() < $b->getPriority()) ? -1 : 1;
        });
        $this->children = new ArrayCollection(iterator_to_array($iterator));

        $child->setParent($this);
        return $this;
    }

    public function removeChild(MenuItem $child): static
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

    public function setParent(?MenuItem $parent): static
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
     */
    public function setPriority($priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return MenuItem[]
     */
    public function getPath(): array
    {
        $path = [];
        $current = $this;

        while (null !== $current) {
            array_unshift($path, $current);
            $current = $current->getParent();
        }

        return $path;
    }

    public function getModule(): ?string
    {
        return $this->module;
    }

    public function setModule(?string $module): static
    {
        $this->module = $module;
        return $this;
    }
}
