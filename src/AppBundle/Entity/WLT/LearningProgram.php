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

namespace AppBundle\Entity\WLT;

use AppBundle\Entity\Company;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WLT\LearningProgramRepository")
 * @ORM\Table(name="wlt_learning_program"),
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"company_id", "project_id"})}))))
 * @UniqueEntity(fields={"company", "project"}, message="company_program.company_training.unique")
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
     * @ORM\ManyToOne(targetEntity="Project")
     * @ORM\JoinColumn(nullable=false)
     * @var Project
     */
    private $project;

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
        return $this->getCompany() . ' - ' . $this->getProject();
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
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return LearningProgram
     */
    public function setProject(Project $project)
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return ActivityRealization[]|Collection
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
