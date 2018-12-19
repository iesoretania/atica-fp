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

namespace AppBundle\Entity\WLT;

use AppBundle\Entity\Company;
use AppBundle\Entity\Edu\Training;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WLT\LearningProgramRepository")
 * @ORM\Table(name="wlt_learning_program",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"company_id", "training_id"})}))))
 * @UniqueEntity(fields={"company", "training"}, message="company_program.company_training.unique")
 */
class LearningProgram
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Company")
     * @ORM\JoinColumn(nullable=false)
     * @var Company
     */
    private $company;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Edu\Training")
     * @ORM\JoinColumn(nullable=false)
     * @var Training
     */
    private $training;

    /**
     * @ORM\ManyToMany(targetEntity="ActivityRealization")
     * @ORM\JoinTable("wlt_learning_program_activity_realization")
     * @var ActivityRealization[]
     */
    private $activityRealizations;

    public function __construct()
    {
        $this->activityRealizations = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getCompany() . ' - ' . $this->getTraining();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Company
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param Company $company
     * @return LearningProgram
     */
    public function setCompany(Company $company)
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @return Training
     */
    public function getTraining()
    {
        return $this->training;
    }

    /**
     * @param Training $training
     * @return LearningProgram
     */
    public function setTraining(Training $training)
    {
        $this->training = $training;
        return $this;
    }

    /**
     * @return ActivityRealization[]
     */
    public function getActivityRealizations()
    {
        return $this->activityRealizations;
    }

    /**
     * @param ActivityRealization[] $activityRealizations
     * @return LearningProgram
     */
    public function setActivityRealizations($activityRealizations)
    {
        $this->activityRealizations = $activityRealizations;
        return $this;
    }
}
