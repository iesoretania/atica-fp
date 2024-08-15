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

namespace App\Service;

use App\Menu\MenuItem;

class MenuBuilderChain
{
    /**
     * @var MenuBuilderInterface[]
     */
    private array $menuBuilders = [];

    private ?MenuItem $menuCache = null;

    public function __construct($menus)
    {
        foreach ($menus as $menu) {
            $this->addMenuBuilder($menu);
        }
    }

    public function addMenuBuilder($menuBuilder): void
    {
        $this->menuBuilders[] = $menuBuilder;
    }

    public function getChain(): array
    {
        return $this->menuBuilders;
    }

    public function getMenu()
    {
        if ($this->menuCache instanceof MenuItem) {
            return $this->menuCache;
        }

        $root = new MenuItem();
        $root
            ->setName('frontpage')
            ->setRouteName('frontpage')
            ->setCaption('menu.frontpage')
            ->setDescription('menu.frontpage.detail')
            ->setIcon('home');

        foreach ($this->menuBuilders as $menuBuilder) {
            $menuStructure = $menuBuilder->getMenuStructure();
            if ($menuStructure) {
                foreach ($menuStructure as $menuItem) {
                    $root->addChild($menuItem);
                }
            }
        }

        $this->menuCache = $root;

        return $root;
    }

    public function clearCache(): void
    {
        $this->menuCache = null;
    }

    /**
     * Búsqueda recursiva de una ruta
     *
     * @param $route
     * @param MenuItem|null $item
     * @return MenuItem|null
     */
    private function checkMenuRouteName($route, MenuItem $item = null)
    {
        if (!$item instanceof MenuItem) {
            return null;
        }

        if ($item->getRouteName() === $route) {
            return $item;
        }

        foreach ($item->getChildren() as $child) {
            $ret = $this->checkMenuRouteName($route, $child);
            if (null !== $ret) {
                return $ret;
            }
        }
        return null;
    }

    /**
     * Devolver menú correspondiente a una ruta
     *
     * @param string $route
     * @return MenuItem|null
     */
    public function getMenuByRouteName($route)
    {
        return $this->checkMenuRouteName($route, $this->getMenu());
    }

    /**
     * Devolver el camino correspondiente a una ruta
     *
     * @param string $route
     * @return MenuItem[]|null
     */
    public function getPathByRouteName($route)
    {
        $item = $this->checkMenuRouteName($route, $this->getMenu());
        return $item !== null ? $item->getPath() : null;
    }
}
