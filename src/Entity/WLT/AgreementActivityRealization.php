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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @ORM\ManyToOne(targetEntity="ActivityRealization", fetch="EAGER")
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
     * @ORM\Column(type="boolean")
     * @var bool
     */
    private $disabled;

    /**
     * @ORM\OneToMany(targetEntity="AgreementActivityRealizationComment", mappedBy="agreementActivityRealization")
     * @ORM\OrderBy({"timestamp":"ASC"})
     * @var AgreementActivityRealizationComment[]|Collection
     */
    private $comments;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
    }

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

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param bool $disabled
     * @return AgreementActivityRealization
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * @return AgreementActivityRealizationComment[]|Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * @param Collection $comments
     * @return AgreementActivityRealization
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
        return $this;
    }
}
