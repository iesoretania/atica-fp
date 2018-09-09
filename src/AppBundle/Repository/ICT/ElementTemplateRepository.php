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

namespace AppBundle\Repository\ICT;

use AppBundle\Entity\Organization;
use Doctrine\ORM\EntityRepository;

class ElementTemplateRepository extends EntityRepository
{

    public function findByOrganization(Organization $organization)
    {
        return $this->createQueryBuilder('et')
            ->orderBy('et.name')
            ->andWhere('et.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getResult();
    }
}
