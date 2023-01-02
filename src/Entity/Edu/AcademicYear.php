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

namespace App\Entity\Edu;

use App\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Edu\AcademicYearRepository")
 * @ORM\Table(name="edu_academic_year")
 */
class AcademicYear
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Organization")
     * @ORM\JoinColumn(nullable=false)
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher")
     * @ORM\JoinColumn(nullable=true)
     * @var Teacher
     */
    private $principal;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher")
     * @ORM\JoinColumn(nullable=true)
     * @var Teacher
     */
    private $financialManager;

    /**
     * @ORM\Column(type="date")
     * @var \DateTime
     */
    private $startDate;

    /**
     * @ORM\Column(type="date")
     * @var \DateTime
     */
    private $endDate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\ReportTemplate")
     * @var ReportTemplate
     */
    private $defaultPortraitTemplate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Edu\ReportTemplate")
     * @var ReportTemplate
     */
    private $defaultLandscapeTemplate;

    public function __toString()
    {
        return $this->getDescription();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     * @return AcademicYear
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;
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
     * @return AcademicYear
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return Teacher|null
     */
    public function getPrincipal()
    {
        return $this->principal;
    }

    /**
     * @param Teacher|null $principal
     * @return AcademicYear
     */
    public function setPrincipal(Teacher $principal = null)
    {
        $this->principal = $principal;
        return $this;
    }

    /**
     * @return Teacher|null
     */
    public function getFinancialManager()
    {
        return $this->financialManager;
    }

    /**
     * @param Teacher|null $financialManager
     * @return AcademicYear
     */
    public function setFinancialManager(Teacher $financialManager = null)
    {
        $this->financialManager = $financialManager;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * @param \DateTime $startDate
     * @return AcademicYear
     */
    public function setStartDate(\DateTimeInterface $startDate)
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * @param \DateTime $endDate
     * @return AcademicYear
     */
    public function setEndDate(\DateTimeInterface $endDate)
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return ReportTemplate
     */
    public function getDefaultPortraitTemplate()
    {
        return $this->defaultPortraitTemplate;
    }

    /**
     * @param ReportTemplate $defaultPortraitTemplate
     * @return AcademicYear
     */
    public function setDefaultPortraitTemplate($defaultPortraitTemplate)
    {
        $this->defaultPortraitTemplate = $defaultPortraitTemplate;
        return $this;
    }

    /**
     * @return ReportTemplate
     */
    public function getDefaultLandscapeTemplate()
    {
        return $this->defaultLandscapeTemplate;
    }

    /**
     * @param ReportTemplate $defaultLandscapeTemplate
     * @return AcademicYear
     */
    public function setDefaultLandscapeTemplate($defaultLandscapeTemplate)
    {
        $this->defaultLandscapeTemplate = $defaultLandscapeTemplate;
        return $this;
    }
}
