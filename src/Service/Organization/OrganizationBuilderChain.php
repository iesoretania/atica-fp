<?php
/*
  Copyright (C) 2018-2025: Luis Ramón López López

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

namespace App\Service\Organization;

use App\Entity\Person;
use Symfony\Contracts\Service\ResetInterface;

class OrganizationBuilderChain implements ResetInterface
{
    /** @var array<int, array> organizaciones ya calculadas en esta petición, por persona */
    private array $cache = [];

    /**
     * @param OrganizationBuilderInterface[] $organizationBuilders
     */
    public function __construct(private $organizationBuilders)
    {
    }

    public function reset(): void
    {
        $this->cache = [];
    }

    /**
     * Cada constructor lanza varias consultas y la lista se pide varias veces en la misma petición (menú, selector
     * de centro, comprobaciones...): se calcula una sola vez por persona.
     */
    final public function getOrganizations(Person $person) : array
    {
        $key = (int) $person->getId();
        if ($key !== 0 && isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        $organizations = $this->buildOrganizations($person);
        if ($key !== 0) {
            $this->cache[$key] = $organizations;
        }

        return $organizations;
    }

    private function buildOrganizations(Person $person): array
    {
        $organizations = [];
        foreach ($this->organizationBuilders as $organizationBuilder) {
            foreach ($organizationBuilder->getOrganizations($person) as $organization) {
                if (!in_array($organization, $organizations, true)) {
                    $organizations[] = $organization;
                }
            }
        }

        return $organizations;
    }
}
