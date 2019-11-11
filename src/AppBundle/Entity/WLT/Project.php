<?php
/*
  Copyright (C) 2018-2019: Luis Ramón López López

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

use AppBundle\Entity\Edu\Group;
use AppBundle\Entity\Edu\StudentEnrollment;
use AppBundle\Entity\Organization;
use AppBundle\Entity\Person;
use AppBundle\Entity\Survey;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repository\WLT\ProjectRepository")
 * @ORM\Table(name="wlt_project")
 */
class Project
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
     * @ORM\JoinColumn(nullable=false)
     * @var Organization
     */
    private $organization;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(nullable=false)
     * @var Person
     */
    private $manager;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Edu\Group")
     * @ORM\JoinTable(name="wlt_project_group")
     * @var Group[]
     */
    private $groups;

    /**
     * @ORM\ManyToMany(targetEntity="AppBundle\Entity\Edu\StudentEnrollment")
     * @ORM\JoinTable(name="wlt_project_student_enrollment")
     * @var StudentEnrollment[]
     */
    private $studentEnrollments;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $studentSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $companySurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $managerFinalSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $managerSurvey;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Survey")
     * @ORM\JoinColumn(onDelete="SET NULL")
     * @var Survey
     */
    private $educationalTutorSurvey;

    /**
     * @ORM\OneToMany(targetEntity="Agreement", mappedBy="project")
     * @var Agreement[]
     */
    private $agreements;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
        $this->studentEnrollments = new ArrayCollection();
        $this->agreements = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Project
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
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
     * @return Project
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
        return $this;
    }

    /**
     * @return Person
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @param Person $manager
     * @return Project
     */
    public function setManager($manager)
    {
        $this->manager = $manager;
        return $this;
    }

    /**
     * @return Group[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param Group[] $groups
     * @return Project
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * @return StudentEnrollment[]
     */
    public function getStudentEnrollments()
    {
        return $this->studentEnrollments;
    }

    /**
     * @param StudentEnrollment[] $studentEnrollments
     * @return Project
     */
    public function setStudentEnrollments($studentEnrollments)
    {
        $this->studentEnrollments = $studentEnrollments;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getStudentSurvey()
    {
        return $this->studentSurvey;
    }

    /**
     * @param Survey $studentSurvey
     * @return Project
     */
    public function setStudentSurvey(Survey $studentSurvey = null)
    {
        $this->studentSurvey = $studentSurvey;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getCompanySurvey()
    {
        return $this->companySurvey;
    }

    /**
     * @param Survey $companySurvey
     * @return Project
     */
    public function setCompanySurvey(Survey $companySurvey = null)
    {
        $this->companySurvey = $companySurvey;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getManagerSurvey()
    {
        return $this->managerSurvey;
    }

    /**
     * @param Survey $managerSurvey
     * @return Project
     */
    public function setManagerSurvey(Survey $managerSurvey = null)
    {
        $this->managerSurvey = $managerSurvey;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getManagerFinalSurvey()
    {
        return $this->managerFinalSurvey;
    }

    /**
     * @param Survey $managerFinalSurvey
     * @return Project
     */
    public function setManagerFinalSurvey($managerFinalSurvey)
    {
        $this->managerFinalSurvey = $managerFinalSurvey;
        return $this;
    }

    /**
     * @return Survey
     */
    public function getEducationalTutorSurvey()
    {
        return $this->educationalTutorSurvey;
    }

    /**
     * @param Survey $educationalTutorSurvey
     * @return Project
     */
    public function setEducationalTutorSurvey($educationalTutorSurvey)
    {
        $this->educationalTutorSurvey = $educationalTutorSurvey;
        return $this;
    }

    /**
     * @return Agreement[]
     */
    public function getAgreements()
    {
        return $this->agreements;
    }

    /**
     * @param Agreement[] $agreements
     * @return Project
     */
    public function setAgreements($agreements)
    {
        $this->agreements = $agreements;
        return $this;
    }
}
