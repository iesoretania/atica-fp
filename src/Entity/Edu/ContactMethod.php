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

namespace App\Entity\Edu;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Edu\ContactMethodRepository")
 * @ORM\Table(name="edu_contact_method"),
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"academic_year_id", "description"})}))))
 * @UniqueEntity(fields={"academicYear", "description"}, message="contact_method.unique")
 */
class ContactMethod
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\AcademicYear")
     * @ORM\JoinColumn(nullable=false)
     * @var ?AcademicYear
     */
    private $academicYear;

    /**
     * @ORM\Column(type="string")
     * @var ?string
     */
    private $description;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $enabled;

    public function __construct()
    {
        $this->enabled = true;
    }

    public function __toString()
    {
        return $this->description ?? '';
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return AcademicYear
     */
    public function getAcademicYear()
    {
        return $this->academicYear;
    }

    /**
     * @param AcademicYear $academicYear
     * @return self
     */
    public function setAcademicYear(AcademicYear $academicYear)
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    /**
     * @return ?string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return bool
     */
    public function getEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return self
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }
}
