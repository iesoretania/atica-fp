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

namespace App\Entity\WLT;

use App\Entity\Person;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WLT\AgreementActivityRealizationRepository")
 * @ORM\Table(name="wlt_agreement_activity_realization")
 */
class AgreementActivityRealization
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Agreement", inversedBy="evaluatedActivityRealizations")
     * @ORM\JoinColumn(nullable=false)
     * @var Agreement
     */
    private $agreement;

    /**
     * @ORM\ManyToOne(targetEntity="ActivityRealization")
     * @ORM\JoinColumn(nullable=false)
     * @var ActivityRealization
     */
    private $activityRealization;

    /**
     * @ORM\ManyToOne(targetEntity="ActivityRealizationGrade")
     * @ORM\JoinColumn(nullable=true)
     * @var ActivityRealizationGrade
     */
    private $grade;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Person")
     * @ORM\JoinColumn(nullable=true)
     * @var Person
     */
    private $gradedBy;

    /**
     * @ORM\Column(type="date", nullable=true)
     * @var \DateTime
     */
    private $gradedOn;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Agreement
     */
    public function getAgreement()
    {
        return $this->agreement;
    }

    /**
     * @param Agreement $agreement
     * @return AgreementActivityRealization
     */
    public function setAgreement($agreement)
    {
        $this->agreement = $agreement;
        return $this;
    }

    /**
     * @return ActivityRealization
     */
    public function getActivityRealization()
    {
        return $this->activityRealization;
    }

    /**
     * @param ActivityRealization $activityRealization
     * @return AgreementActivityRealization
     */
    public function setActivityRealization($activityRealization)
    {
        $this->activityRealization = $activityRealization;
        return $this;
    }

    /**
     * @return ActivityRealizationGrade
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param ActivityRealizationGrade $grade
     * @return AgreementActivityRealization
     */
    public function setGrade(ActivityRealizationGrade $grade = null)
    {
        $this->grade = $grade;
        return $this;
    }

    /**
     * @return Person
     */
    public function getGradedBy()
    {
        return $this->gradedBy;
    }

    /**
     * @param Person $gradedBy
     * @return AgreementActivityRealization
     */
    public function setGradedBy(Person $gradedBy = null)
    {
        $this->gradedBy = $gradedBy;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getGradedOn()
    {
        return $this->gradedOn;
    }

    /**
     * @param \DateTime $gradedOn
     * @return AgreementActivityRealization
     */
    public function setGradedOn(\DateTimeInterface $gradedOn = null)
    {
        $this->gradedOn = $gradedOn;
        return $this;
    }
}
