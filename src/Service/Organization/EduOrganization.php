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

namespace App\Service\Organization;

use App\Entity\Person;
use App\Repository\Edu\EduOrganizationRepository;
use App\Service\OrganizationBuilderInterface;
use Symfony\Component\Security\Core\Security;

class EduOrganization implements OrganizationBuilderInterface
{
    /**
     * @var EduOrganizationRepository
     */
    private $eduOrganizationRepository;
    /**
     * @var Security
     */
    private $security;

    public function __construct(EduOrganizationRepository $eduOrganizationRepository, Security $security)
    {
        $this->eduOrganizationRepository = $eduOrganizationRepository;
        $this->security = $security;
    }

    public function getOrganizations(Person $person): array
    {
        $organizations = $this->eduOrganizationRepository->findByCurrentStudent($person);
        $organizations = array_merge($organizations, $this->eduOrganizationRepository->findByCurrentTeacher($person));
        return $organizations;
    }
}
