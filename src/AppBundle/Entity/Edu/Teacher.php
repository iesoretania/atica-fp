<?php

namespace AppBundle\Entity\Edu;

use AppBundle\Entity\Person;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="edu_teacher",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"person_id", "academic_year_id"})}))
 */
class Teacher
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Person")
     * @ORM\JoinColumn(nullable=false)
     * @var Person
     */
    private $person;

    /**
     * @ORM\ManyToOne(targetEntity="AcademicYear")
     * @ORM\JoinColumn(nullable=false)
     * @var AcademicYear
     */
    private $academicYear;

    /**
     * @ORM\ManyToOne(targetEntity="Department")
     * @ORM\JoinColumn(nullable=true)
     * @var Department
     */
    private $department;

    /**
     * @ORM\OneToMany(targetEntity="Teaching", mappedBy="teacher")
     * @var Teaching[]
     */
    private $teachings;

    public function __construct()
    {
        $this->teachings = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Person
     */
    public function getPerson()
    {
        return $this->person;
    }

    /**
     * @param Person $person
     * @return Teacher
     */
    public function setPerson(Person $person)
    {
        $this->person = $person;
        return $this;
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
     * @return Teacher
     */
    public function setAcademicYear(AcademicYear $academicYear)
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    /**
     * @return Department
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param Department $department
     * @return Teacher
     */
    public function setDepartment(Department $department = null)
    {
        $this->department = $department;
        return $this;
    }

    /**
     * @return Teaching[]
     */
    public function getTeachings()
    {
        return $this->teachings;
    }
}
