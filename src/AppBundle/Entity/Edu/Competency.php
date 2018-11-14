<?php

namespace AppBundle\Entity\Edu;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="edu_competency",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"training_id", "code"})}))))
 */
class Competency
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Training", inversedBy="competencies")
     * @ORM\JoinColumn(nullable=false)
     * @var Training
     */
    private $training;

    /**
     * @ORM\Column(type="string", nullable=false)
     * @var string
     */
    private $code;

    /**
     * @ORM\Column(type="text", nullable=false)
     * @var string
     */
    private $description;

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
     * @return Competency
     */
    public function setTraining(Training $training)
    {
        $this->training = $training;
        return $this;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return Competency
     */
    public function setCode($code)
    {
        $this->code = $code;
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
     * @return Competency
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
}
