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

namespace AppBundle\Entity\Edu;

use AppBundle\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="edu_organization")
 */
class EducationalOrganization
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\OneToOne(targetEntity="AcademicYear")
     * @var AcademicYear
     */
    private $currentAcademicYear;

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     * @return EducationalOrganization
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return AcademicYear
     */
    public function getCurrentAcademicYear()
    {
        return $this->currentAcademicYear;
    }

    /**
     * @param AcademicYear $currentAcademicYear
     * @return EducationalOrganization
     */
    public function setCurrentAcademicYear(AcademicYear $currentAcademicYear)
    {
        $this->currentAcademicYear = $currentAcademicYear;
        return $this;
    }
}
