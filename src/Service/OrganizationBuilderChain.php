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

namespace App\Service;

use App\Entity\Person;

class OrganizationBuilderChain
{
    /**
     * @var OrganizationBuilderInterface[]
     */
    private $organizationBuilders;

    /**
     * @param OrganizationBuilderInterface[] $organizationBuilders
     */
    public function __construct($organizationBuilders)
    {
        $this->organizationBuilders = $organizationBuilders;
    }

    final public function getOrganizations(Person $person) : array
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
