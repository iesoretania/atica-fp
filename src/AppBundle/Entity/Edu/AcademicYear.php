<?php

namespace AppBundle\Entity\Edu;

use AppBundle\Entity\Organization;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
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
     * @ORM\ManyToOne(targetEntity="AppBundle\Entity\Organization")
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
}
