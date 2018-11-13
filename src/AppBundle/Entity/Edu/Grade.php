<?php

namespace AppBundle\Entity\Edu;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="edu_grade")
 */
class Grade
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Training")
     * @ORM\JoinColumn(nullable=false)
     * @var Training
     */
    private $training;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    private $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return Grade
     */
    public function setTraining(Training $training)
    {
        $this->training = $training;
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
     * @return Grade
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
