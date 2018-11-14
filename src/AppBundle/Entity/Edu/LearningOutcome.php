<?php

namespace AppBundle\Entity\Edu;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="edu_learning_outcome",
 *     uniqueConstraints={@ORM\UniqueConstraint(columns={"subject_id", "code"})}))))
 */
class LearningOutcome
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Subject", inversedBy="learningOutcomes")
     * @ORM\JoinColumn(nullable=false)
     * @var Subject
     */
    private $subject;

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
     * @return Subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param Subject $subject
     * @return LearningOutcome
     */
    public function setSubject(Subject $subject)
    {
        $this->subject = $subject;
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
     * @return LearningOutcome
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
     * @return LearningOutcome
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
}
