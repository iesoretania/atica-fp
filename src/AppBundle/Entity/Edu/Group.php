<?php

namespace AppBundle\Entity\Edu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="edu_group")
 */
class Group
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Grade")
     * @ORM\JoinColumn(nullable=false)
     * @var Grade
     */
    private $grade;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Teacher")
     * @ORM\JoinColumn(nullable=true)
     * @var Teacher
     */
    private $tutor;

    /**
     * @ORM\OneToMany(targetEntity="Teaching", mappedBy="group")
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
     * @return Grade
     */
    public function getGrade()
    {
        return $this->grade;
    }

    /**
     * @param Grade $grade
     * @return Group
     */
    public function setGrade(Grade $grade)
    {
        $this->grade = $grade;
        return $this;
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
     * @return Group
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return Teacher
     */
    public function getTutor()
    {
        return $this->tutor;
    }

    /**
     * @param Teacher $tutor
     * @return Group
     */
    public function setTutor(Teacher $tutor = null)
    {
        $this->tutor = $tutor;
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
