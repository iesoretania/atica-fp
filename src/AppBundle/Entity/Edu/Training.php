<?php

namespace AppBundle\Entity\Edu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="edu_training")
 */
class Training
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="AcademicYear")
     * @ORM\JoinColumn(nullable=false)
     * @var AcademicYear
     */
    private $academicYear;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="Competency", mappedBy="training")
     * @ORM\OrderBy({"code": "ASC"})
     * @var Competency[]
     */
    private $competencies;

    public function __construct()
    {
        $this->competencies = new ArrayCollection();
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
     * @return Training
     */
    public function setName($name)
    {
        $this->name = $name;
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
     * @return Training
     */
    public function setAcademicYear(AcademicYear $academicYear)
    {
        $this->academicYear = $academicYear;
        return $this;
    }

    /**
     * @return Competency[]
     */
    public function getCompetencies()
    {
        return $this->competencies;
    }
}