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

namespace AppBundle\Entity\WPT;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="wpt_report")
 */
class Report
{
    const GRADE_NEGATIVE = 0;
    const GRADE_POSITIVE = 1;
    const GRADE_EXCELENT = 2;

    /**
     * @ORM\Id
     * @ORM\OneToOne(targetEntity="Agreement", inversedBy="report")
     * @var Agreement
     */
    protected $agreement;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $workActivities;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $professionalCompetence;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $organizationalCompetence;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $relationalCompetence;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $contingencyResponse;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $otherDescription1;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $other1;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @var string
     */
    protected $otherDescription2;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $other2;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $proposedChanges;

    /**
     * @ORM\Column(type="date")
     * @var \DateTime
     */
    protected $signDate;

    /**
     * @return Agreement
     */
    public function getAgreement()
    {
        return $this->agreement;
    }

    /**
     * @param Agreement $agreement
     * @return Report
     */
    public function setAgreement($agreement)
    {
        $this->agreement = $agreement;
        return $this;
    }

    /**
     * @return string
     */
    public function getWorkActivities()
    {
        return $this->workActivities;
    }

    /**
     * @param string $workActivities
     * @return Report
     */
    public function setWorkActivities($workActivities)
    {
        $this->workActivities = $workActivities;
        return $this;
    }

    /**
     * @return int
     */
    public function getProfessionalCompetence()
    {
        return $this->professionalCompetence;
    }

    /**
     * @param int $professionalCompetence
     * @return Report
     */
    public function setProfessionalCompetence($professionalCompetence)
    {
        $this->professionalCompetence = $professionalCompetence;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrganizationalCompetence()
    {
        return $this->organizationalCompetence;
    }

    /**
     * @param int $organizationalCompetence
     * @return Report
     */
    public function setOrganizationalCompetence($organizationalCompetence)
    {
        $this->organizationalCompetence = $organizationalCompetence;
        return $this;
    }

    /**
     * @return int
     */
    public function getRelationalCompetence()
    {
        return $this->relationalCompetence;
    }

    /**
     * @param int $relationalCompetence
     * @return Report
     */
    public function setRelationalCompetence($relationalCompetence)
    {
        $this->relationalCompetence = $relationalCompetence;
        return $this;
    }

    /**
     * @return int
     */
    public function getContingencyResponse()
    {
        return $this->contingencyResponse;
    }

    /**
     * @param int $contingencyResponse
     * @return Report
     */
    public function setContingencyResponse($contingencyResponse)
    {
        $this->contingencyResponse = $contingencyResponse;
        return $this;
    }

    /**
     * @return string
     */
    public function getOtherDescription1()
    {
        return $this->otherDescription1;
    }

    /**
     * @param string $otherDescription1
     * @return Report
     */
    public function setOtherDescription1($otherDescription1)
    {
        $this->otherDescription1 = $otherDescription1;
        return $this;
    }

    /**
     * @return int
     */
    public function getOther1()
    {
        return $this->other1;
    }

    /**
     * @param int $other1
     * @return Report
     */
    public function setOther1($other1)
    {
        $this->other1 = $other1;
        return $this;
    }

    /**
     * @return string
     */
    public function getOtherDescription2()
    {
        return $this->otherDescription2;
    }

    /**
     * @param string $otherDescription2
     * @return Report
     */
    public function setOtherDescription2($otherDescription2)
    {
        $this->otherDescription2 = $otherDescription2;
        return $this;
    }

    /**
     * @return int
     */
    public function getOther2()
    {
        return $this->other2;
    }

    /**
     * @param int $other2
     * @return Report
     */
    public function setOther2($other2)
    {
        $this->other2 = $other2;
        return $this;
    }

    /**
     * @return string
     */
    public function getProposedChanges()
    {
        return $this->proposedChanges;
    }

    /**
     * @param string $proposedChanges
     * @return Report
     */
    public function setProposedChanges($proposedChanges)
    {
        $this->proposedChanges = $proposedChanges;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSignDate()
    {
        return $this->signDate;
    }

    /**
     * @param \DateTime $signDate
     * @return Report
     */
    public function setSignDate($signDate)
    {
        $this->signDate = $signDate;
        return $this;
    }
}
